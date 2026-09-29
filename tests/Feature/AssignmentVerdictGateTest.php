<?php

namespace Tests\Feature;

use App\Jobs\EvaluateCandidateJob;
use App\Jobs\GenerateInterviewQuestionsJob;
use App\Jobs\GenerateTakeHomeAssignmentJob;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Services\EvaluationOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AssignmentVerdictGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Mail::fake();
    }

    public function test_queues_take_home_assignment_for_hire_verdict(): void
    {
        $evaluation = $this->evaluatedCandidate('hire');

        $this->mockOrchestrator();
        $this->runJob($evaluation);

        Queue::assertPushed(GenerateTakeHomeAssignmentJob::class, fn ($job) => $job->candidateId === $evaluation->candidate->id);
        Queue::assertPushed(GenerateInterviewQuestionsJob::class);
    }

    public function test_queues_take_home_assignment_for_strong_hire_verdict(): void
    {
        $evaluation = $this->evaluatedCandidate('strong_hire');

        $this->mockOrchestrator();
        $this->runJob($evaluation);

        Queue::assertPushed(GenerateTakeHomeAssignmentJob::class, fn ($job) => $job->candidateId === $evaluation->candidate->id);
    }

    public function test_does_not_queue_take_home_assignment_for_maybe_or_reject_verdicts(): void
    {
        foreach (['maybe', 'no_hire', 'strong_no_hire', 'insufficient_data'] as $verdict) {
            $evaluation = $this->evaluatedCandidate($verdict);

            $this->mockOrchestrator();
            $this->runJob($evaluation);

            Queue::assertNotPushed(GenerateTakeHomeAssignmentJob::class);
        }
    }

    private function evaluatedCandidate(string $verdict): Evaluation
    {
        $candidate = Candidate::factory()->evaluated()->create();

        return Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => 7.0,
            'verdict' => $verdict,
            'narrative_summary' => 'Summary.',
            'strengths' => [],
            'concerns' => [],
            'interview_focus_areas' => [],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
        ]);
    }

    private function mockOrchestrator(): void
    {
        $this->mock(EvaluationOrchestrator::class, function ($mock) {
            $mock->shouldReceive('evaluateCandidate')->once();
        });
    }

    private function runJob(Evaluation $evaluation): void
    {
        (new EvaluateCandidateJob($evaluation->candidate->id))->handle(app(EvaluationOrchestrator::class));
    }
}
