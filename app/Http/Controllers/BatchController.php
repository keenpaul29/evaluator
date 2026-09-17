<?php

namespace App\Http\Controllers;

use App\Enums\CandidateStatus;
use App\Jobs\EvaluateCandidateJob;
use App\Models\BatchJob;
use App\Models\Candidate;
use App\Models\EvaluationProgress;
use App\Models\HrUser;
use App\Services\GithubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BatchController extends Controller
{
    public function index()
    {
        $batches = BatchJob::withCount('candidates')->latest()->get();

        return view('batches.index', compact('batches'));
    }

    public function create()
    {
        return view('batches.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'csv_file' => 'required|file|mimes:csv,txt|max:1024',
        ]);

        $file = $request->file('csv_file');
        $rows = $this->parseCsv($file);

        if (empty($rows)) {
            return back()->with('error', 'CSV file is empty or invalid.');
        }

        if (count($rows) > 50) {
            return back()->with('error', 'Maximum batch size is 50 candidates.');
        }

        $batch = BatchJob::create([
            'name' => $request->name,
            'csv_filename' => $file->getClientOriginalName(),
            'total_candidates' => count($rows),
            'status' => 'pending',
            'hr_user_id' => Auth::id() ?? HrUser::first()?->id,
        ]);

        $githubService = app(GithubService::class);

        foreach ($rows as $index => $row) {
            $name = trim($row['name'] ?? '');
            $email = trim($row['email'] ?? '');
            $githubUsername = trim($row['github_username'] ?? '');
            $repos = trim($row['repos'] ?? '');

            if (! $name || ! $email || ! $githubUsername) {
                continue;
            }

            $candidate = Candidate::create([
                'name' => $name,
                'email' => $email,
                'github_username' => $githubUsername,
                'status' => CandidateStatus::Submitted,
                'submission_type' => 'hr_initiated',
                'submitted_by' => Auth::id() ?? HrUser::first()?->id,
                'batch_id' => $batch->id,
            ]);

            if ($repos) {
                $repoUrls = array_map('trim', explode(',', $repos));
                $githubService->syncCandidateRepoUrls($repoUrls, $candidate->id);
            }

            $progress = EvaluationProgress::create([
                'event_id' => Str::uuid(),
                'candidate_id' => $candidate->id,
                'status' => 'queued',
                'current_step' => 'queued',
                'steps_total' => 3,
            ]);

            EvaluateCandidateJob::dispatch($candidate->id, $progress->id);
        }

        $batch->update(['status' => 'processing', 'started_at' => now()]);

        return redirect()->route('batches.show', $batch)
            ->with('success', "Batch '{$batch->name}' started with ".count($rows).' candidates.');
    }

    public function show(BatchJob $batch)
    {
        $batch->load('candidates.evaluation');

        return view('batches.show', compact('batch'));
    }

    private function parseCsv($file): array
    {
        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return [];
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            return [];
        }

        $headers = array_map(fn ($h) => strtolower(trim($h)), $headers);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $rows[] = array_combine($headers, $row);
            }
        }

        fclose($handle);

        return $rows;
    }
}
