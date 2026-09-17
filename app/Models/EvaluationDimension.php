<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationDimension extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_id',
        'dimension',
        'score',
        'weight',
        'justification',
        'evidence',
    ];

    protected $casts = [
        'score' => 'float',
        'weight' => 'float',
        'evidence' => 'array',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function getDimensionLabel(): string
    {
        return match ($this->dimension) {
            'code_quality' => 'Code Quality',
            'technical_judgment' => 'Technical Judgment',
            'colvalues_alignment' => 'ColValues Alignment',
            'communication' => 'Communication',
            'problem_complexity' => 'Problem Complexity',
            'learning_trajectory' => 'Learning Trajectory',
            'technical_breadth' => 'Technical Breadth',
            default => $this->dimension,
        };
    }

    public function getScoreColor(): string
    {
        if ($this->score >= 8) {
            return 'green';
        }
        if ($this->score >= 6) {
            return 'emerald';
        }
        if ($this->score >= 4) {
            return 'amber';
        }
        if ($this->score >= 2) {
            return 'orange';
        }

        return 'red';
    }
}
