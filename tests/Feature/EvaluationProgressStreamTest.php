<?php

namespace Tests\Feature;

use App\Enums\CandidateStatus;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Models\EvaluationProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvaluationProgressStreamTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_progress_streams_progress_and_complete_events(): void
    {
        $candidate = Candidate::factory()->evaluated()->create();
        $evaluation = Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => 8.5,
            'verdict' => 'strong_hire',
            'narrative_summary' => 'Excellent.',
            'strengths' => [],
            'concerns' => [],
            'interview_focus_areas' => [],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
        ]);

        $progress = EvaluationProgress::create([
            'event_id' => Str::uuid()->toString(),
            'candidate_id' => $candidate->id,
            'status' => 'complete',
            'current_step' => 'done',
            'progress_percent' => 100,
            'steps_total' => 3,
            'steps_completed' => 3,
        ]);

        $response = $this->get("/api/evaluations/{$candidate->id}/progress");

        $response->assertStatus(200);
        $this->assertHeaderStartsWith($response, 'text/event-stream');

        $content = $response->streamedContent();

        $this->assertStringContainsString('event: progress', $content);
        $this->assertStringContainsString('event: complete', $content);
        $this->assertStringContainsString($progress->event_id, $content);
        $this->assertStringContainsString('8.5', $content);
        $this->assertStringContainsString('strong_hire', $content);
    }

    public function test_failed_progress_streams_error_event(): void
    {
        $candidate = Candidate::factory()->create(['status' => CandidateStatus::Submitted]);
        $progress = EvaluationProgress::create([
            'event_id' => Str::uuid()->toString(),
            'candidate_id' => $candidate->id,
            'status' => 'failed',
            'current_step' => 'idle',
            'error_message' => 'GitHub API rate limit exceeded',
        ]);

        $response = $this->get("/api/evaluations/{$candidate->id}/progress");

        $response->assertStatus(200);
        $this->assertHeaderStartsWith($response, 'text/event-stream');

        $content = $response->streamedContent();

        $this->assertStringContainsString('event: error', $content);
        $this->assertStringContainsString('GitHub API rate limit exceeded', $content);
        $this->assertStringContainsString($progress->event_id, $content);
    }

    public function test_stream_resumes_from_last_event_id(): void
    {
        $candidate = Candidate::factory()->evaluated()->create();
        Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => 7.0,
            'verdict' => 'hire',
            'narrative_summary' => 'Summary.',
            'strengths' => [],
            'concerns' => [],
            'interview_focus_areas' => [],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
        ]);

        $progress = EvaluationProgress::create([
            'event_id' => Str::uuid()->toString(),
            'candidate_id' => $candidate->id,
            'status' => 'complete',
            'current_step' => 'done',
            'progress_percent' => 100,
            'steps_total' => 3,
            'steps_completed' => 3,
        ]);

        $response = $this->get("/api/evaluations/{$candidate->id}/progress", [
            'Last-Event-ID' => $progress->event_id,
        ]);

        $response->assertStatus(200);

        $content = $response->streamedContent();

        $this->assertStringContainsString('event: complete', $content);
        $this->assertStringContainsString('7', $content);
        $this->assertStringContainsString('hire', $content);
    }

    public function test_stream_emits_complete_for_evaluated_candidate_without_progress(): void
    {
        $candidate = Candidate::factory()->evaluated()->create();
        Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => 6.5,
            'verdict' => 'maybe',
            'narrative_summary' => 'Summary.',
            'strengths' => [],
            'concerns' => [],
            'interview_focus_areas' => [],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
        ]);

        $response = $this->get("/api/evaluations/{$candidate->id}/progress");

        $response->assertStatus(200);
        $this->assertHeaderStartsWith($response, 'text/event-stream');

        $content = $response->streamedContent();

        $this->assertStringContainsString('event: complete', $content);
        $this->assertStringContainsString('6.5', $content);
        $this->assertStringContainsString('maybe', $content);
    }

    private function assertHeaderStartsWith($response, string $value): void
    {
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString($value, $response->headers->get('Content-Type', ''));
    }
}
