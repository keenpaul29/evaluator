<?php

namespace Tests\Feature;

use App\Jobs\EvaluateCandidateJob;
use App\Models\BatchJob;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Models\HrUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AddCandidatesToBatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        HrUser::factory()->create();
        Bus::fake();
    }

    private function makeBatch(array $attributes = []): BatchJob
    {
        return BatchJob::create(array_merge([
            'name' => 'Q3 Roster',
            'total_candidates' => 1,
            'status' => 'processing',
            'started_at' => now(),
        ], $attributes));
    }

    public function test_adds_unevaluated_candidates_and_queues_evaluation(): void
    {
        $batch = $this->makeBatch();
        Candidate::factory()->create(['batch_id' => $batch->id]);
        $new = Candidate::factory()->create();

        $this->post(route('batches.add-candidates', $batch), [
            'candidate_ids' => [$new->id],
        ])->assertSessionHas('success');

        $this->assertSame($batch->id, $new->fresh()->batch_id);
        $this->assertSame(2, $batch->fresh()->total_candidates);
        $this->assertDatabaseHas('evaluation_progress', [
            'candidate_id' => $new->id,
            'status' => 'queued',
        ]);

        Bus::assertDispatched(EvaluateCandidateJob::class, 1);
        Bus::assertDispatched(EvaluateCandidateJob::class, function (EvaluateCandidateJob $job) use ($new) {
            return $job->candidateId === $new->id;
        });
    }

    public function test_adds_evaluated_candidates_without_requeueing(): void
    {
        $batch = $this->makeBatch(['status' => 'complete', 'completed_at' => now(), 'processed_count' => 1]);
        Candidate::factory()->evaluated()->create(['batch_id' => $batch->id]);

        $evaluated = Candidate::factory()->evaluated()->create();
        Evaluation::create([
            'candidate_id' => $evaluated->id,
            'overall_score' => 8.0,
            'verdict' => 'hire',
            'narrative_summary' => 'Summary.',
            'strengths' => [],
            'concerns' => [],
            'interview_focus_areas' => [],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
        ]);

        $this->post(route('batches.add-candidates', $batch), [
            'candidate_ids' => [$evaluated->id],
        ])->assertSessionHas('success');

        $this->assertSame($batch->id, $evaluated->fresh()->batch_id);

        $batch->refresh();
        $this->assertSame(2, $batch->total_candidates);
        $this->assertSame(2, $batch->processed_count);
        $this->assertSame('complete', $batch->status);

        Bus::assertNothingDispatched(EvaluateCandidateJob::class);
        $this->assertDatabaseCount('evaluation_progress', 0);
    }

    public function test_batch_returns_to_processing_when_unevaluated_candidates_are_queued(): void
    {
        $batch = $this->makeBatch(['status' => 'complete', 'completed_at' => now(), 'processed_count' => 1]);
        Candidate::factory()->evaluated()->create(['batch_id' => $batch->id]);
        $unevaluated = Candidate::factory()->create();

        $this->post(route('batches.add-candidates', $batch), [
            'candidate_ids' => [$unevaluated->id],
        ])->assertSessionHas('success');

        $this->assertSame('processing', $batch->fresh()->status);

        Bus::assertDispatched(EvaluateCandidateJob::class, 1);
    }

    public function test_ignores_candidates_already_in_batch(): void
    {
        $batch = $this->makeBatch();
        $inBatch = Candidate::factory()->create(['batch_id' => $batch->id]);

        $this->post(route('batches.add-candidates', $batch), [
            'candidate_ids' => [$inBatch->id],
        ])->assertSessionHas('error');

        $this->assertSame(1, $batch->fresh()->total_candidates);
        $this->assertSame($batch->id, $inBatch->fresh()->batch_id);

        Bus::assertNothingDispatched(EvaluateCandidateJob::class);
    }

    public function test_requires_candidate_ids(): void
    {
        $batch = $this->makeBatch();

        $this->post(route('batches.add-candidates', $batch), [])
            ->assertSessionHasErrors('candidate_ids');
    }
}
