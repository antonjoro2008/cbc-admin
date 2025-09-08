<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subject_id',
        'title',
        'description',
        'grade_level',
        'created_by',
        'paper_code',
        'paper_number',
        'year',
        'exam_body',
        'duration_minutes',
        'instructions',
        'uses_answer_sheet',
        'status'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'uses_answer_sheet' => 'boolean',
        'year' => 'integer',
        'duration_minutes' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($assessment) {
            if (auth()->check()) {
                $assessment->created_by = auth()->id();
            }
        });
    }

    /**
     * Get the subject that the assessment belongs to.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the user who created the assessment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the sections for the assessment.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(AssessmentSection::class);
    }

    /**
     * Get the questions for the assessment.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Get the attempts for the assessment.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }
}