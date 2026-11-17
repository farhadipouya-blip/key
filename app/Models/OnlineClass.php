<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineClass extends Model
{
    protected $fillable = [
        'title', 'description', 'platform', 'meeting_id', 'join_url', 'host_url',
        'starts_at', 'duration_minutes', 'course_id', 'teacher_id', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ClassAttendance::class, 'class_id');
    }
}
