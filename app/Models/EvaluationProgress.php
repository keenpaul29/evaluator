<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EvaluationProgress extends Model
{
    protected $fillable = [
        'event_id',
        'candidate_id',
        'status',
        'current_step',
        'progress_percent',
        'steps_total',
        'steps_completed',
        'error_message',
    ];

    protected static function booted(): void
    {
        static::creating(function (EvaluationProgress $progress) {
            if (empty($progress->event_id)) {
                $progress->event_id = Str::uuid();
            }
        });
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function updateProgress(string $step, int $stepsCompleted, ?string $status = null): void
    {
        $percent = $this->steps_total > 0
            ? min(100, (int) round(($stepsCompleted / $this->steps_total) * 100))
            : 0;

        $this->update([
            'current_step' => $step,
            'steps_completed' => $stepsCompleted,
            'progress_percent' => $percent,
            'status' => $status ?? $this->status,
        ]);
    }

    public function markComplete(): void
    {
        $this->update([
            'status' => 'complete',
            'progress_percent' => 100,
            'current_step' => 'done',
        ]);
    }

    public function markFailed(string $message): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $message,
        ]);
    }
}
