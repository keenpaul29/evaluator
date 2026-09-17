<?php

namespace App\Models;

use App\Enums\CandidateStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'github_username',
        'linkedin_url',
        'portfolio_url',
        'status',
        'submitted_by',
        'submission_type',
        'notes',
    ];

    protected $casts = [
        'status' => CandidateStatus::class,
        'submission_type' => 'string',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::updating(function (Candidate $candidate) {
            cache()->forget('dashboard_stats');
        });

        static::created(function (Candidate $candidate) {
            cache()->forget('dashboard_stats');
        });
    }

    public function submitter()
    {
        return $this->belongsTo(HrUser::class, 'submitted_by');
    }

    public function repositories()
    {
        return $this->hasMany(Repository::class);
    }

    public function evaluation()
    {
        return $this->hasOne(Evaluation::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(EvaluationProgress::class);
    }

    public function getLatestProgressAttribute(): ?EvaluationProgress
    {
        return $this->progress()->latest()->first();
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', CandidateStatus::Submitted);
    }

    public function scopeAnalyzed($query)
    {
        return $query->where('status', CandidateStatus::Analyzing);
    }

    public function scopeEvaluated($query)
    {
        return $query->where('status', CandidateStatus::Evaluated);
    }

    public function scopeShortlisted($query)
    {
        return $query->where('status', CandidateStatus::Shortlisted);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', CandidateStatus::Rejected);
    }
}
