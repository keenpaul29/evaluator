<?php

namespace App\Jobs;

use App\Enums\AssignmentStatus;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Services\AssignmentGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateTakeHomeAssignmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public int $candidateId,
        public int $evaluationId
    ) {
        $this->onQueue('evaluations');
    }

    public function handle(AssignmentGenerationService $service): void
    {
        $candidate = Candidate::find($this->candidateId);
        $evaluation = Evaluation::with('dimensions')->find($this->evaluationId);

        if (! $candidate || ! $evaluation) {
            return;
        }

        $assignment = $service->generate($candidate, $evaluation);

        if (! $assignment && $evaluation->assignment) {
            $evaluation->assignment->update(['status' => AssignmentStatus::Error]);
        }

        if (! $assignment) {
            Log::warning('Take-home assignment generation returned empty', [
                'candidate_id' => $this->candidateId,
                'evaluation_id' => $this->evaluationId,
            ]);
        }
    }
}
