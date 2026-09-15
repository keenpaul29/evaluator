<?php

namespace App\Http\Controllers;

use App\Jobs\EvaluateCandidateJob;
use App\Models\Candidate;
use App\Services\GithubService;
use Illuminate\Http\Request;

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
            'status' => 'submitted',
            'submission_type' => 'candidate_self_service',
        ]);

        $githubService = app(GithubService::class);
        $githubService->syncCandidateRepoUrls($validated['repo_urls'], $candidate->id);

        EvaluateCandidateJob::dispatch($candidate->id);

        return redirect()->route('apply.success')
            ->with('candidate_id', $candidate->id);
    }

    public function success()
    {
        $candidateId = session('candidate_id');

        return view('apply.success', ['candidateId' => $candidateId]);
    }
}
