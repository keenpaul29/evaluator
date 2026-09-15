<?php

namespace App\Services;

use App\Models\Candidate;
use Illuminate\Support\Facades\Log;

class EvaluationOrchestrator
{
    private GithubService $github;

    private RepositoryAnalyzer $analyzer;

    private AiEvaluationService $ai;

    public function __construct(
        GithubService $github,
        RepositoryAnalyzer $analyzer,
        AiEvaluationService $ai
    ) {
        $this->github = $github;
        $this->analyzer = $analyzer;
        $this->ai = $ai;
    }

    public function evaluateCandidate(Candidate $candidate): void
    {
        try {
            $candidate->update(['status' => 'analyzing']);

            if ($candidate->github_username) {
                $this->github->syncCandidateRepos($candidate->github_username, $candidate->id);
            }

            $repositories = $candidate->repositories;
            if ($repositories->isEmpty()) {
                Log::warning("No repositories found for candidate {$candidate->id}");

                $candidate->update(['status' => 'submitted']);

                return;
            }

            $analyses = [];
            foreach ($repositories as $repo) {
                try {
                    $analysis = $this->analyzer->analyze($repo);
                    $repo->update(['analyzed_at' => now()]);
                    $analyses[] = $analysis;
                } catch (\Exception $e) {
                    Log::error("Failed to analyze repo {$repo->full_name}: ".$e->getMessage());
                }
            }

            if (empty($analyses)) {
                Log::warning("No repos analyzed for candidate {$candidate->id}");

                $candidate->update(['status' => 'submitted']);

                return;
            }

            $this->ai->evaluate($candidate, $analyses);

        } catch (\Exception $e) {
            Log::error("Evaluation failed for candidate {$candidate->id}: ".$e->getMessage());
            $candidate->update(['status' => 'submitted']);

            throw $e;
        }
    }
}
