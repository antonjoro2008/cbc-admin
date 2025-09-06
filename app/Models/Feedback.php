<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feedback extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'attempt_answer_id',
        'feedback_text',
        'ai_generated',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ai_generated' => 'boolean',
    ];

    /**
     * Get the attempt answer that this feedback belongs to.
     */
    public function attemptAnswer(): BelongsTo
    {
        return $this->belongsTo(AttemptAnswer::class, 'attempt_answer_id');
    }

    /**
     * Get the media for this feedback.
     */
    public function media(): HasMany
    {
        return $this->hasMany(FeedbackMedia::class);
    }
}
