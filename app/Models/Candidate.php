<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'status' => 'string',
        'submission_type' => 'string',
    ];

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

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeAnalyzed($query)
    {
        return $query->where('status', 'analyzing');
    }

    public function scopeEvaluated($query)
    {
        return $query->where('status', 'evaluated');
    }

    public function scopeShortlisted($query)
    {
        return $query->where('status', 'shortlisted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
