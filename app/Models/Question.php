<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'assessment_id',
        'question_text',
        'question_type',
        'category_tag',
        'marks',
        'section_id',
        'question_number',
        'parent_question_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'marks' => 'integer',
        'question_number' => 'integer',
    ];

    /**
     * Get the assessment that the question belongs to.
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * Get the section that the question belongs to.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(AssessmentSection::class, 'section_id');
    }

    /**
     * Get the parent question.
     */
    public function parentQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'parent_question_id');
    }

    /**
     * Get the child questions.
     */
    public function childQuestions(): HasMany
    {
        return $this->hasMany(Question::class, 'parent_question_id');
    }

    /**
     * Get the answers for this question.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * Get the media for this question.
     */
    public function media(): HasMany
    {
        return $this->hasMany(QuestionMedia::class);
    }

    /**
     * Get the attempt answers for this question.
     */
    public function attemptAnswers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }
}
