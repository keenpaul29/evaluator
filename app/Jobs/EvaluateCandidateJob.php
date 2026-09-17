<?php

namespace App\Jobs;

use App\Enums\CandidateStatus;
use App\Mail\EvaluationCompletedMail;
use App\Models\Candidate;
use App\Models\EvaluationProgress;
use App\Services\EvaluationOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EvaluateCandidateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public int $candidateId,
        public ?int $progressId = null
    ) {
        $this->onQueue('evaluations');
    }

    public function handle(EvaluationOrchestrator $orchestrator): void
    {
        $candidate = Candidate::findOrFail($this->candidateId);

        $progress = $this->progressId
            ? EvaluationProgress::find($this->progressId)
            : null;

        $orchestrator->evaluateCandidate($candidate, $progress);

        $candidate->refresh();

        if ($candidate->status === CandidateStatus::Evaluated && $candidate->evaluation) {
            $this->sendNotification($candidate);
        }
    }

    private function sendNotification(Candidate $candidate): void
    {
        try {
            $submitter = $candidate->submitter;

            if ($submitter && $submitter->email) {
                Mail::to($submitter->email)->send(new EvaluationCompletedMail(
                    $candidate,
                    $candidate->evaluation
                ));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send evaluation notification', [
                'candidate_id' => $candidate->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $candidate = Candidate::find($this->candidateId);

        if ($candidate && $candidate->status === CandidateStatus::Analyzing) {
            $candidate->update(['status' => CandidateStatus::Submitted]);
        }

        if ($this->progressId) {
            $progress = EvaluationProgress::find($this->progressId);
            if ($progress) {
                $progress->markFailed($exception->getMessage());
            }
        }
    }
}
