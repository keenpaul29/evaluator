<?php

namespace App\Http\Controllers;

use App\Enums\CandidateStatus;
use App\Jobs\EvaluateCandidateJob;
use App\Models\Candidate;
use App\Models\EvaluationProgress;
use App\Services\GithubService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicApplyController extends Controller
{
    public function show()
    {
        return view('apply.show');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'github_username' => 'required|string|max:255',
            'repo_urls' => 'required|array|min:1|max:5',
            'repo_urls.*' => 'url',
            'linkedin_url' => 'nullable|url|max:500',
            'portfolio_url' => 'nullable|url|max:500',
            'notes' => 'nullable|string|max:2000',
        ]);

        $candidate = Candidate::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'github_username' => $validated['github_username'],
            'linkedin_url' => $validated['linkedin_url'] ?? null,
            'portfolio_url' => $validated['portfolio_url'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => CandidateStatus::Submitted,
            'submission_type' => 'candidate_self_service',
        ]);

        $githubService = app(GithubService::class);
        $synced = $githubService->syncCandidateRepoUrls($validated['repo_urls'], $candidate->id);

        if (empty($synced)) {
            $candidate->delete();

            return back()
                ->withInput()
                ->withErrors(['repo_urls' => 'Unable to synchronize any of the provided repository URLs. Please verify the URLs are public and accessible.']);
        }

        $progress = EvaluationProgress::create([
            'event_id' => Str::uuid(),
            'candidate_id' => $candidate->id,
            'status' => 'queued',
            'current_step' => 'queued',
            'steps_total' => 3,
        ]);

        EvaluateCandidateJob::dispatch($candidate->id, $progress->id);

        return redirect()->route('apply.success')
            ->with('candidate_id', $candidate->id);
    }

    public function success()
    {
        $candidateId = session('candidate_id');

        return view('apply.success', ['candidateId' => $candidateId]);
    }
}
