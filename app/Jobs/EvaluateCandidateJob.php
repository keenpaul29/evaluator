<?php

namespace App\Jobs;

use App\Models\Candidate;
use App\Services\EvaluationOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateCandidateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public int $candidateId
    ) {
        $this->onQueue('evaluations');
    }

    public function handle(EvaluationOrchestrator $orchestrator): void
    {
        $candidate = Candidate::findOrFail($this->candidateId);

        $orchestrator->evaluateCandidate($candidate);
    }

    public function failed(\Throwable $exception): void
    {
        $candidate = Candidate::find($this->candidateId);

        if ($candidate && $candidate->status === 'analyzing') {
            $candidate->update(['status' => 'submitted']);
        }
    }
}
