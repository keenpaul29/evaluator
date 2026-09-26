<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepositoryAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'repository_id',
        'total_files_analyzed',
        'total_lines_analyzed',
        'primary_languages',
        'has_readme',
        'has_tests',
        'has_ci_config',
        'has_documentation',
        'commit_frequency_score',
        'avg_commit_quality_score',
        'code_complexity_estimate',
        'architectural_patterns',
        'dependencies_analysis',
        'authenticity_score',
        'authenticity_flags',
        'artifact_file_paths',
        'commit_samples',
        'artifact_excerpts',
        'analyzed_at',
    ];

    protected $casts = [
        'primary_languages' => 'array',
        'has_readme' => 'boolean',
        'has_tests' => 'boolean',
        'has_ci_config' => 'boolean',
        'has_documentation' => 'boolean',
        'architectural_patterns' => 'array',
        'dependencies_analysis' => 'array',
        'authenticity_flags' => 'array',
        'authenticity_score' => 'integer',
        'artifact_file_paths' => 'array',
        'commit_samples' => 'array',
        'artifact_excerpts' => 'array',
        'analyzed_at' => 'datetime',
        'commit_frequency_score' => 'float',
        'avg_commit_quality_score' => 'float',
        'total_files_analyzed' => 'integer',
        'total_lines_analyzed' => 'integer',
    ];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }
}
