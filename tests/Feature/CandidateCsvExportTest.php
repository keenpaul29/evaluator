<?php

namespace Tests\Feature;

use App\Enums\CandidateStatus;
use App\Jobs\EvaluateCandidateJob;
use App\Models\BatchJob;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Models\HrUser;
use App\Models\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CandidateCsvExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        HrUser::factory()->create();
    }

    public function test_exports_candidates_with_repos_and_evaluation_results(): void
    {
        $candidate = Candidate::factory()->create([
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'github_username' => 'alice',
            'status' => CandidateStatus::Evaluated,
        ]);

        Repository::create([
            'candidate_id' => $candidate->id,
            'github_repo_id' => 9001,
            'name' => 'rpg-app',
            'full_name' => 'alice/rpg-app',
            'html_url' => 'https://github.com/alice/rpg-app',
            'primary_language' => 'PHP',
        ]);

        Evaluation::create([
            'candidate_id' => $candidate->id,
            'overall_score' => 8.5,
            'verdict' => 'strong_hire',
            'narrative_summary' => 'Strong signals.',
            'strengths' => [],
            'concerns' => [],
            'interview_focus_areas' => [],
            'ai_model_used' => 'gemini-1.5-flash',
            'evaluated_at' => now(),
        ]);

        $response = $this->get(route('candidates.export'));

        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('name,email,github_username,repos,status,overall_score,verdict', $csv);
        $this->assertStringContainsString('"Alice Smith",alice@example.com,alice,https://github.com/alice/rpg-app,evaluated,8.5,strong_hire', $csv);
    }

    public function test_exports_respect_filters(): void
    {
        Candidate::factory()->evaluated()->create([
            'name' => 'High Scorer',
            'email' => 'high@example.com',
            'github_username' => 'high',
        ]);
        Candidate::factory()->evaluated()->create([
            'name' => 'Low Scorer',
            'email' => 'low@example.com',
            'github_username' => 'low',
        ]);

        foreach (Candidate::all() as $candidate) {
            Evaluation::create([
                'candidate_id' => $candidate->id,
                'overall_score' => $candidate->name === 'High Scorer' ? 9.0 : 4.0,
                'verdict' => $candidate->name === 'High Scorer' ? 'strong_hire' : 'no_hire',
                'narrative_summary' => 'Summary.',
                'strengths' => [],
                'concerns' => [],
                'interview_focus_areas' => [],
                'ai_model_used' => 'gemini-1.5-flash',
                'evaluated_at' => now(),
            ]);
        }

        $csv = $this->get(route('candidates.export', ['min_score' => 7]))->streamedContent();

        $this->assertStringContainsString('high@example.com', $csv);
        $this->assertStringNotContainsString('low@example.com', $csv);
    }

    public function test_batch_results_can_be_exported_and_reimported_into_a_new_batch(): void
    {
        $batch = BatchJob::create([
            'name' => 'January Intake',
            'csv_filename' => 'original.csv',
            'total_candidates' => 2,
            'status' => 'complete',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $bob = Candidate::factory()->evaluated()->create([
            'name' => 'Bob Jones',
            'email' => 'bob@example.com',
            'github_username' => 'bob',
            'batch_id' => $batch->id,
        ]);

        Repository::create([
            'candidate_id' => $bob->id,
            'github_repo_id' => 9002,
            'name' => 'web-app',
            'full_name' => 'bob/web-app',
            'html_url' => 'https://github.com/bob/web-app',
            'primary_language' => 'PHP',
        ]);

        foreach ($batch->candidates as $candidate) {
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
        }

        $response = $this->get(route('batches.export', $batch));

        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('"Bob Jones",bob@example.com,bob,https://github.com/bob/web-app', $csv);

        Candidate::where('batch_id', $batch->id)->delete();

        Bus::fake();

        Http::fake([
            'api.github.com/repos/bob/web-app' => Http::response([
                'id' => 9002,
                'name' => 'web-app',
                'full_name' => 'bob/web-app',
                'html_url' => 'https://github.com/bob/web-app',
                'default_branch' => 'main',
                'language' => 'PHP',
                'fork' => false,
            ]),
        ]);

        $response = $this->post(route('batches.store'), [
            'name' => 'Re-imported Batch',
            'csv_file' => UploadedFile::fake()->createWithContent('roundtrip.csv', $csv),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $reimported = BatchJob::where('name', 'Re-imported Batch')->first();
        $this->assertNotNull($reimported);
        $this->assertSame(1, $reimported->total_candidates);
        $this->assertDatabaseHas('candidates', [
            'name' => 'Bob Jones',
            'email' => 'bob@example.com',
            'github_username' => 'bob',
            'batch_id' => $reimported->id,
            'status' => 'submitted',
        ]);

        Bus::assertDispatched(EvaluateCandidateJob::class, 1);
    }
}
