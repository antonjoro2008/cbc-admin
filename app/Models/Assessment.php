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
     * Get the sections for the assessment through questions.
     */
    public function sections()
    {
        return $this->hasManyThrough(
            AssessmentSection::class,
            Question::class,
            'assessment_id', // Foreign key on questions table
            'id', // Foreign key on sections table
            'id', // Local key on assessments table
            'section_id' // Local key on questions table
        );
    }

    /**
     * Get sections with their questions for this assessment.
     * This method provides a more reliable way to get sections with questions
     * when using eager loading.
     */
    public function getSectionsWithQuestions()
    {
        // Get all questions for this assessment with their media and answers
        $questions = $this->questions()->with(['section', 'media', 'answers'])->orderBy('question_number')->get();
        
        // Group questions by section
        $sectionsWithQuestions = $questions->groupBy('section_id');
        
        // Get unique sections and attach their questions
        $sections = AssessmentSection::whereIn('id', $sectionsWithQuestions->keys())
            ->orderBy('section_order')
            ->get();
        
        // Attach the filtered questions to each section
        $sections->each(function ($section) use ($sectionsWithQuestions) {
            $sectionQuestions = $sectionsWithQuestions->get($section->id, collect())
                ->sortBy('question_number');
            $section->setRelation('questions', $sectionQuestions);
        });
        
        return $sections;
    }

    /**
     * Get the questions for the assessment.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('question_number');
    }

    /**
     * Get the attempts for the assessment.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }
}