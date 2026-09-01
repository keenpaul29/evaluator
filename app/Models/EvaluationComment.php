<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_id',
        'hr_user_id',
        'comment',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function hrUser(): BelongsTo
    {
        return $this->belongsTo(HrUser::class, 'hr_user_id');
    }
}
