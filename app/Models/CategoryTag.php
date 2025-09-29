<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryTag extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tag',
    ];

    /**
     * Get the questions that use this category tag.
     */
    public function questions()
    {
        return $this->hasMany(Question::class, 'category_tag', 'tag');
    }
}
