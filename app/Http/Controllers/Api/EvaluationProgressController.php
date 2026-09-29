<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\EvaluationProgress;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvaluationProgressController extends Controller
{
    public function show(Candidate $candidate): StreamedResponse
    {
        return response()->stream(function () use ($candidate) {
            $lastEventId = request()->header('Last-Event-ID');

            if ($lastEventId) {
                $progress = EvaluationProgress::where('event_id', $lastEventId)->first();

                if ($progress) {
                    $this->sendEvent('progress', [
                        'status' => $progress->status,
                        'current_step' => $progress->current_step,
                        'progress_percent' => $progress->progress_percent,
                        'steps_completed' => $progress->steps_completed,
                        'steps_total' => $progress->steps_total,
                    ], $progress->event_id);

                    if ($progress->status === 'complete') {
                        $this->sendCompleteEvent($candidate, $progress->event_id);

                        return;
                    }

                    if ($progress->status === 'failed') {
                        $this->sendEvent('error', [
                            'message' => $progress->error_message ?? 'Evaluation failed',
                        ], $progress->event_id);

                        return;
                    }
                }
            }

            $lastStatus = null;
            $maxIterations = 600;
            $iteration = 0;

            while ($iteration < $maxIterations) {
                $candidate->refresh();
                $progress = $candidate->latestProgress;

                if ($progress) {
                    if ($progress->status !== $lastStatus) {
                        $this->sendEvent('progress', [
                            'status' => $progress->status,
                            'current_step' => $progress->current_step,
                            'progress_percent' => $progress->progress_percent,
                            'steps_completed' => $progress->steps_completed,
                            'steps_total' => $progress->steps_total,
                        ], $progress->event_id);

                        $lastStatus = $progress->status;
                    }

                    if ($progress->status === 'complete') {
                        $this->sendCompleteEvent($candidate, $progress->event_id);

                        return;
                    }

                    if ($progress->status === 'failed') {
                        $this->sendEvent('error', [
                            'message' => $progress->error_message ?? 'Evaluation failed',
                        ], $progress->event_id);

                        return;
                    }
                }

                if ($candidate->status->value === 'evaluated' && $candidate->evaluation) {
                    $this->sendEvent('complete', [
                        'overall_score' => $candidate->evaluation->overall_score,
                        'verdict' => $candidate->evaluation->verdict,
                        'evaluation_id' => $candidate->evaluation->id,
                    ]);

                    return;
                }

                sleep(2);
                $iteration++;
            }

            $this->sendEvent('error', ['message' => 'Timeout waiting for evaluation']);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function sendEvent(string $type, array $data, ?string $eventId = null): void
    {
        $payload = json_encode($data);

        echo "event: {$type}\n";
        if ($eventId) {
            echo "id: {$eventId}\n";
        }
        echo "data: {$payload}\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    private function sendCompleteEvent(Candidate $candidate, string $eventId): void
    {
        $candidate->load('evaluation');

        $this->sendEvent('complete', [
            'overall_score' => $candidate->evaluation->overall_score,
            'verdict' => $candidate->evaluation->verdict,
            'evaluation_id' => $candidate->evaluation->id,
        ], $eventId);
    }
}
