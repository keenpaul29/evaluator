<?php

namespace App\Services;

use App\Enums\CandidateStatus;
use App\Models\Candidate;
use App\Models\EvaluationProgress;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    public function evaluateCandidate(Candidate $candidate, ?EvaluationProgress $progress = null): void
    {
        if (! $progress) {
            $progress = EvaluationProgress::create([
                'event_id' => Str::uuid(),
                'candidate_id' => $candidate->id,
                'status' => 'queued',
                'current_step' => 'initializing',
                'progress_percent' => 0,
                'steps_total' => 3,
                'steps_completed' => 0,
            ]);
        }

        try {
            $candidate->update(['status' => CandidateStatus::Analyzing]);
            $progress->updateProgress('syncing_repos', 0, 'analyzing');

            $this->syncRepos($candidate, $progress);

            $repositories = $candidate->repositories;
            if ($repositories->isEmpty()) {
                Log::warning("No repositories found for candidate {$candidate->id}");
                $progress->markFailed('No repositories found');

                $candidate->update(['status' => CandidateStatus::Submitted]);

                return;
            }

            $analyses = $this->analyzeRepos($candidate, $repositories, $progress);

            if (empty($analyses)) {
                Log::warning("No repos analyzed for candidate {$candidate->id}");
                $progress->markFailed('No repos could be analyzed');

                $candidate->update(['status' => CandidateStatus::Submitted]);

                return;
            }

            $progress->updateProgress('ai_scoring', 2, 'scoring');

            $this->ai->evaluate($candidate, $analyses);

            $progress->markComplete();

        } catch (\Exception $e) {
            Log::error("Evaluation failed for candidate {$candidate->id}: ".$e->getMessage());
            $progress->markFailed($e->getMessage());
            $candidate->update(['status' => CandidateStatus::Submitted]);

            throw $e;
        }
    }

    private function syncRepos(Candidate $candidate, EvaluationProgress $progress): void
    {
        if ($candidate->github_username) {
            $progress->updateProgress('fetching_repos', 0, 'analyzing');
            $this->github->syncCandidateRepos($candidate->github_username, $candidate->id);
        }
    }

    private function analyzeRepos(Candidate $candidate, $repositories, EvaluationProgress $progress): array
    {
        $totalRepos = $repositories->count();
        $progress->steps_total = $totalRepos + 2;
        $progress->save();

        $analyses = [];
        $step = 1;

        foreach ($repositories as $repo) {
            $progress->updateProgress("analyzing_repo_{$repo->name}", $step);

            try {
                $analysis = $this->analyzer->analyze($repo);
                $repo->update(['analyzed_at' => now()]);
                $analyses[] = $analysis;
            } catch (\Exception $e) {
                Log::error("Failed to analyze repo {$repo->full_name}: ".$e->getMessage());
            }

            $step++;
        }

        return $analyses;
    }
}
