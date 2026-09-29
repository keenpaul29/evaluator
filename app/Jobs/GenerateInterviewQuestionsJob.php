<?php

namespace App\Jobs;

use App\Models\Candidate;
use App\Models\Evaluation;
use App\Services\InterviewQuestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateInterviewQuestionsJob implements ShouldQueue
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

    public function handle(InterviewQuestionService $service): void
    {
        $candidate = Candidate::find($this->candidateId);
        $evaluation = Evaluation::with('dimensions')->find($this->evaluationId);

        if (! $candidate || ! $evaluation) {
            return;
        }

        $service->generate($candidate, $evaluation);
    }
}
