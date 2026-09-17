<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchJob extends Model
{
    protected $fillable = [
        'name',
        'csv_filename',
        'total_candidates',
        'processed_count',
        'failed_count',
        'status',
        'started_at',
        'completed_at',
        'hr_user_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function hrUser(): BelongsTo
    {
        return $this->belongsTo(HrUser::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'batch_id');
    }

    public function incrementProcessed(): void
    {
        $this->increment('processed_count');
        $this->checkCompletion();
    }

    public function incrementFailed(): void
    {
        $this->increment('failed_count');
        $this->checkCompletion();
    }

    private function checkCompletion(): void
    {
        $total = $this->processed_count + $this->failed_count;

        if ($total >= $this->total_candidates) {
            $this->update([
                'status' => $this->failed_count > 0 ? 'partial_failure' : 'complete',
                'completed_at' => now(),
            ]);
        }
    }
}
