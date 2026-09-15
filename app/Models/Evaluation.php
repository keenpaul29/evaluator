<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'overall_score',
        'verdict',
        'onboarding_friction',
        'onboarding_friction_reason',
        'narrative_summary',
        'strengths',
        'concerns',
        'interview_focus_areas',
        'ai_model_used',
        'evaluated_at',
        'reviewed_by_hr',
        'hr_notes',
    ];

    protected $casts = [
        'overall_score' => 'float',
        'strengths' => 'array',
        'concerns' => 'array',
        'interview_focus_areas' => 'array',
        'evaluated_at' => 'datetime',
        'reviewed_by_hr' => 'boolean',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function dimensions(): HasMany
    {
        return $this->hasMany(EvaluationDimension::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(EvaluationComment::class);
    }

    public function getDimensionScore(string $dimension): ?float
    {
        $dim = $this->dimensions()->where('dimension', $dimension)->first();

        return $dim?->score;
    }

    public function getVerdictLabel(): string
    {
        return match ($this->verdict) {
            'strong_hire' => 'Strong Hire',
            'hire' => 'Hire',
            'maybe' => 'Maybe',
            'no_hire' => 'No Hire',
            'strong_no_hire' => 'Strong No Hire',
            default => 'Unknown',
        };
    }

    public function getVerdictColor(): string
    {
        return match ($this->verdict) {
            'strong_hire' => 'green',
            'hire' => 'emerald',
            'maybe' => 'amber',
            'no_hire' => 'orange',
            'strong_no_hire' => 'red',
            default => 'gray',
        };
    }
}
