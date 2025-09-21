<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenUsage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'attempt_id',
        'tokens_used',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tokens_used' => 'decimal:2',
    ];

    /**
     * Get the assessment attempt that this token usage belongs to.
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssessmentAttempt::class, 'attempt_id');
    }
}
