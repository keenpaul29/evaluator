<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    protected $fillable = [
        'candidate_id',
        'evaluation_id',
        'status',
        'token',
        'brief',
        'ai_model_used',
        'generated_at',
        'dispatched_at',
        'due_at',
        'submitted_repo_url',
        'reflection',
        'submitted_at',
        'reviewed_by',
        'review_notes',
    ];

    protected $casts = [
        'status' => AssignmentStatus::class,
        'brief' => 'array',
        'generated_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'due_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class, 'candidate_id');
    }

    public function getStatusLabel(): string
    {
        return $this->status->label();
    }

    public function getStatusColor(): string
    {
        return $this->status->color();
    }

    public function scopePending($query)
    {
        return $query->whereNotIn('status', [AssignmentStatus::Complete, AssignmentStatus::Error]);
    }
}
