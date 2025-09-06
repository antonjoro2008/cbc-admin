<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnswerMedia extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'answer_id',
        'media_type',
        'file_path',
        'caption',
    ];

    /**
     * Get the answer that this media belongs to.
     */
    public function answer(): BelongsTo
    {
        return $this->belongsTo(Answer::class);
    }

    /**
     * Check if the media is an image.
     */
    public function isImage(): bool
    {
        return $this->media_type === 'image';
    }

    /**
     * Check if the media is a video.
     */
    public function isVideo(): bool
    {
        return $this->media_type === 'video';
    }

    /**
     * Check if the media is an audio file.
     */
    public function isAudio(): bool
    {
        return $this->media_type === 'audio';
    }

    /**
     * Check if the media is a document.
     */
    public function isDocument(): bool
    {
        return in_array($this->media_type, ['pdf', 'doc']);
    }
}
