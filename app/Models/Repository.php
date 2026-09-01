<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Repository extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'github_repo_id',
        'name',
        'full_name',
        'description',
        'html_url',
        'default_branch',
        'primary_language',
        'stars_count',
        'forks_count',
        'open_issues_count',
        'created_at_github',
        'updated_at_github',
        'topics',
        'is_fork',
        'fork_parent_name',
        'analyzed_at',
    ];

    protected $casts = [
        'topics' => 'array',
        'is_fork' => 'boolean',
        'created_at_github' => 'datetime',
        'updated_at_github' => 'datetime',
        'analyzed_at' => 'datetime',
        'stars_count' => 'integer',
        'forks_count' => 'integer',
        'open_issues_count' => 'integer',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(RepositoryAnalysis::class);
    }

    public function isAnalyzed(): bool
    {
        return $this->analyzed_at !== null;
    }
}
