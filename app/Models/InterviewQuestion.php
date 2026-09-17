<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewQuestion extends Model
{
    protected $fillable = [
        'evaluation_id',
        'dimension',
        'question',
        'repo_reference',
        'file_reference',
        'why_ask',
        'generated_at',
        'batch_id',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }
}
