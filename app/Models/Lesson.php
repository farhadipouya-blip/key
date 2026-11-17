<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    protected $fillable = [
        'course_id', 'title', 'description', 'type', 'video_path', 'content',
        'media_id', 'duration_minutes', 'order', 'is_free_preview',
    ];

    protected function casts(): array
    {
        return ['is_free_preview' => 'boolean'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
