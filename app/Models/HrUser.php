<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrUser extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function submittedCandidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'submitted_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(EvaluationComment::class, 'hr_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
