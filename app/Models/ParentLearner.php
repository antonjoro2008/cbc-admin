<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentLearner extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'student_user_id',
        'name',
        'grade_level',
    ];

    /**
     * Get the parent that owns the learner.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Linked student account used for assessments (optional).
     */
    public function studentAccount(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }
}