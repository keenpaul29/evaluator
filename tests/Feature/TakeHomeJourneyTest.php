<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Enums\CandidateStatus;
use App\Jobs\EvaluateCandidateJob;
use App\Jobs\GenerateTakeHomeAssignmentJob;
use App\Models\Assignment;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Services\AssignmentGenerationService;
use App\Services\EvaluationOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TakeHomeJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.ai.provider' => 'gemini',
            'services.gemini.api_key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-1.5-flash',
        ]);

        Mail::fake();
    }

    public function test_full_journey_from_hire_verdict_to_candidate_submission(): void
    {
        $this->fakeGeminiBrief();

        $candidate = Candidate::factory()->create([
            'name' => 'Priya Sharma',
            'email' => 'priya@example.com',
            'github_username' => 'priya',
            'status' => CandidateStatus::Analyzing,
        ]);

        $evaluation = $this->evaluatedCandidate($candidate, 'hire', 7.8);

        $this->mockOrchestrator();

        (new EvaluateCandidateJob($candidate->id))->handle(app(EvaluationOrchestrator::class));

        $assignment = Assignment::where('evaluation_id', $evaluation->id)->first();

        $this->assertNotNull($assignment, 'Assignment should be generated for a hire verdict.');
        $this->assertSame(AssignmentStatus::Generated, $assignment->status);

        $candidate->refresh();
        $this->assertSame(CandidateStatus::Evaluated, $candidate->status);

        $brief = $assignment->brief;
        $this->assertSame('Evolve a multi-tenant learning platform', $brief['title']);
        $this->assertNotEmpty($brief['deliverables']);
        $this->assertNotEmpty($brief['review_criteria']);
        $this->assertArrayHasKey('ai_use_note', $brief);

        $hrResponse = $this->post(route('candidates.assignment.dispatch', $candidate));
        $hrResponse->assertRedirect();
        $hrResponse->assertSessionHas('success');

        $assignment->refresh();
        $this->assertSame(AssignmentStatus::Dispatched, $assignment->status);
        $this->assertNotNull($assignment->dispatched_at);
        $this->assertSame(
            AssignmentGenerationService::DEFAULT_DUE_DAYS,
            (int) now()->startOfDay()->diffInDays($assignment->due_at->copy()->startOfDay(), false)
        );

        $candidatePage = $this->actingAs($candidate->submitter)
            ->get(route('candidates.show', $candidate));
        $candidatePage->assertOk();
        $candidatePage->assertSee('Take-Home Assignment');
        $candidatePage->assertSee(route('assignments.show', $assignment->token));

        $this->get(route('assignments.show', $assignment->token))
            ->assertOk()
            ->assertSee($brief['objective'])
            ->assertSee($brief['title']);

        $submit = $this->post(route('assignments.submit', $assignment->token), [
            'submitted_repo_url' => 'https://github.com/priya/take-home',
            'reflection' => 'I modelled the tenant scope first, then shipped in small commits with tests. What surprised me was how much the reporting query needed indexing.',
        ]);
        $submit->assertSessionHas('success');

        $assignment->refresh();
        $this->assertSame(AssignmentStatus::Submitted, $assignment->status);
        $this->assertSame('https://github.com/priya/take-home', $assignment->submitted_repo_url);
        $this->assertNotNull($assignment->submitted_at);

        $this->get(route('candidates.show', $candidate))
            ->assertOk()
            ->assertSee('https://github.com/priya/take-home');

        $assignment->update([
            'status' => AssignmentStatus::UnderReview,
            'reviewed_by' => $candidate->submitter?->name,
            'review_notes' => 'Clear scope, good tenancy decisions, honest reflection.',
        ]);

        $this->get(route('candidates.show', $candidate))
            ->assertOk()
            ->assertSee('Under Review');
    }

    public function test_no_assignment_for_rejected_verdict(): void
    {
        Queue::fake();

        $this->fakeGeminiBrief();

        $candidate = Candidate::factory()->create(['status' => CandidateStatus::Analyzing]);
        $evaluation = $this->evaluatedCandidate($candidate, 'no_hire', 3.1);

        $this->mockOrchestrator();

        (new EvaluateCandidateJob($candidate->id))->handle(app(EvaluationOrchestrator::class));

        Queue::assertNotPushed(GenerateTakeHomeAssignmentJob::class);
        $this->assertDatabaseCount('assignments', 0);

        $this->post(route('candidates.assignment.dispatch', $candidate))
            ->assertSessionHas('error');
    }

    public function test_dispatch_blocks_submission_until_sent(): void
    {
        $this->fakeGeminiBrief();

        $candidate = Candidate::factory()->evaluated()->create();
        $evaluation = $this->evaluatedCandidate($candidate, 'strong_hire', 8.9);

        $assignment = app(AssignmentGenerationService::class)->generate($candidate, $evaluation);

        $this->assertSame(AssignmentStatus::Generated, $assignment->status);

        $this->get(route('assignments.show', $assignment->token))->assertNotFound();

        $this->post(route('assignments.submit', $assignment->token), [
            'submitted_repo_url' => 'https://github.com/priya/take-home',
            'reflection' => 'A reflection that is comfortably longer than the fifty character minimum.',
        ])->assertNotFound();

        $this->assertSame(AssignmentStatus::Generated, $assignment->fresh()->status);
    }

    private function fakeGeminiBrief(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => "```json\n".json_encode([
                                    'title' => 'Evolve a multi-tenant learning platform',
                                    'objective' => 'Build a small course-authoring API with strict per-tenant data isolation and a Monday-morning report.',
                                    'context' => 'ColoredCow sustains interactive-video learning platforms for education NGOs, where reporting has to stay dependable for teachers who read it first thing every week.',
                                    'deliverables' => [
                                        'Laravel REST API for courses, lessons and enrolment',
                                        'Per-tenant isolation enforced in every query path',
                                        'A weekly engagement report endpoint',
                                        'Test suite plus a README that runs from scratch',
                                    ],
                                    'review_criteria' => [
                                        'Tests are meaningful and pass in CI',
                                        'Code is review-ready: clear naming, small surface, documented decisions',
                                        'Runs without us: a new developer can boot it unaided',
                                        'Scope respected: shipped incrementally with a usable slice early',
                                    ],
                                    'timebox' => '7 days, ~12-15 focused hours.',
                                    'ai_use_note' => 'Use AI as a thinking partner. AI must not replace understanding, and honest attribution of what you used it for is part of the work.',
                                    'submission' => [
                                        'repo_url' => 'A fresh GitHub repository',
                                        'reflection' => 'Approach, decisions, obstacles and learnings.',
                                    ],
                                ])."\n```"],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);
    }

    private function evaluatedCandidate(Candidate $candidate, string $verdict, float $score): Evaluation
    {
        return Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => $score,
            'verdict' => $verdict,
            'narrative_summary' => 'Solid backend engineer with clear ownership instincts.',
            'strengths' => ['Consistent test coverage across services'],
            'concerns' => ['Documentation trails the code in places'],
            'interview_focus_areas' => ['Walk through a documentation decision'],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
        ]);
    }

    private function mockOrchestrator(): void
    {
        $this->mock(EvaluationOrchestrator::class, function ($mock) {
            $mock->shouldReceive('evaluateCandidate')
                ->once()
                ->andReturnUsing(function (Candidate $candidate) {
                    $candidate->update(['status' => CandidateStatus::Evaluated]);
                });
        });
    }
}
