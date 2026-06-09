<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentBookEntry extends Model
{
    public const PERIOD_TYPES = ['daily', 'weekly', 'monthly', 'termly'];

    public const CBE_LEVELS = ['BE', 'AE', 'ME', 'EE'];

    protected $fillable = [
        'teacher_id',
        'student_id',
        'institution_id',
        'classroom_id',
        'period_type',
        'period_start',
        'period_end',
        'learning_area',
        'strand',
        'activity_observed',
        'score_percent',
        'cbe_level',
        'strengths',
        'gaps_to_address',
        'next_steps',
        'teacher_notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'score_percent' => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
