<?php

namespace Tests\Feature;

use App\Jobs\EvaluateCandidateJob;
use App\Models\BatchJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BatchImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    public function test_csv_upload_creates_batch_and_candidates_and_dispatches_jobs(): void
    {
        $csv = $this->csvContent([
            ['name' => 'Alice Smith', 'email' => 'alice@example.com', 'github_username' => 'alice', 'repos' => ''],
            ['name' => 'Bob Jones', 'email' => 'bob@example.com', 'github_username' => 'bob', 'repos' => ''],
        ]);

        $response = $this->post('/batches', [
            'name' => 'January Intake',
            'csv_file' => UploadedFile::fake()->createWithContent('january.csv', $csv),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('batch_jobs', [
            'name' => 'January Intake',
            'csv_filename' => 'january.csv',
            'total_candidates' => 2,
            'status' => 'processing',
        ]);

        $batch = BatchJob::where('name', 'January Intake')->first();
        $this->assertSame(2, $batch->candidates()->count());
        $this->assertNotNull($batch->started_at);

        $this->assertDatabaseHas('candidates', [
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'github_username' => 'alice',
            'status' => 'submitted',
            'batch_id' => $batch->id,
        ]);

        $this->assertDatabaseHas('candidates', [
            'name' => 'Bob Jones',
            'email' => 'bob@example.com',
            'batch_id' => $batch->id,
        ]);

        $this->assertDatabaseCount('evaluation_progress', 2);

        Bus::assertDispatched(EvaluateCandidateJob::class, 2);
    }

    public function test_csv_upload_syncs_repositories_from_csv(): void
    {
        Http::fake([
            'api.github.com/repos/alice/portfolio' => Http::response([
                'id' => 555,
                'name' => 'portfolio',
                'full_name' => 'alice/portfolio',
                'html_url' => 'https://github.com/alice/portfolio',
                'default_branch' => 'main',
                'language' => 'PHP',
                'fork' => false,
            ]),
        ]);

        $csv = $this->csvContent([
            ['name' => 'Alice Smith', 'email' => 'alice@example.com', 'github_username' => 'alice', 'repos' => 'https://github.com/alice/portfolio'],
        ]);

        $response = $this->post('/batches', [
            'name' => 'With Repos',
            'csv_file' => UploadedFile::fake()->createWithContent('with-repos.csv', $csv),
        ]);

        $this->assertDatabaseHas('repositories', [
            'github_repo_id' => 555,
            'full_name' => 'alice/portfolio',
        ]);
    }

    public function test_csv_upload_skips_rows_missing_required_fields(): void
    {
        $csv = $this->csvContent([
            ['name' => '', 'email' => 'missing@example.com', 'github_username' => 'ghost', 'repos' => ''],
            ['name' => 'Valid Person', 'email' => 'valid@example.com', 'github_username' => 'valid', 'repos' => ''],
        ]);

        $this->post('/batches', [
            'name' => 'Filtered Batch',
            'csv_file' => UploadedFile::fake()->createWithContent('filtered.csv', $csv),
        ]);

        $batch = BatchJob::where('name', 'Filtered Batch')->first();
        $this->assertNotNull($batch);

        $this->assertDatabaseMissing('candidates', ['email' => 'missing@example.com']);
        $this->assertDatabaseHas('candidates', ['email' => 'valid@example.com']);
        $this->assertSame(1, $batch->candidates()->count());
        $this->assertSame(2, $batch->total_candidates);
    }

    public function test_empty_csv_returns_error_and_creates_no_batch(): void
    {
        $csv = "name,email,github_username,repos\n";

        $response = $this->post('/batches', [
            'name' => 'Empty Batch',
            'csv_file' => UploadedFile::fake()->createWithContent('empty.csv', $csv),
        ]);

        $response->assertSessionHas('error', 'CSV file is empty or invalid.');
        $this->assertDatabaseMissing('batch_jobs', ['name' => 'Empty Batch']);
        Bus::assertNotDispatched(EvaluateCandidateJob::class);
    }

    public function test_csv_exceeding_batch_size_limit_is_rejected(): void
    {
        $rows = [];
        foreach (range(1, 51) as $i) {
            $rows[] = ['name' => "Candidate {$i}", 'email' => "candidate{$i}@example.com", 'github_username' => "user{$i}", 'repos' => ''];
        }

        $csv = $this->csvContent($rows);

        $response = $this->post('/batches', [
            'name' => 'Too Big',
            'csv_file' => UploadedFile::fake()->createWithContent('too-big.csv', $csv),
        ]);

        $response->assertSessionHas('error', 'Maximum batch size is 50 candidates.');
        $this->assertDatabaseMissing('batch_jobs', ['name' => 'Too Big']);
        Bus::assertNotDispatched(EvaluateCandidateJob::class);
    }

    public function test_batch_requires_candidate_data_from_csv(): void
    {
        $response = $this->post('/batches', [
            'name' => 'No File',
        ]);

        $response->assertSessionHasErrors(['csv_file']);
        $this->assertDatabaseCount('batch_jobs', 0);
    }

    public function test_batch_job_name_is_required(): void
    {
        $csv = $this->csvContent([
            ['name' => 'Alice Smith', 'email' => 'alice@example.com', 'github_username' => 'alice', 'repos' => ''],
        ]);

        $response = $this->post('/batches', [
            'csv_file' => UploadedFile::fake()->createWithContent('no-name.csv', $csv),
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    private function csvContent(array $rows): string
    {
        $lines = ['name,email,github_username,repos'];

        foreach ($rows as $row) {
            $lines[] = implode(',', [
                $row['name'],
                $row['email'],
                $row['github_username'],
                $row['repos'],
            ]);
        }

        return implode("\n", $lines);
    }
}
