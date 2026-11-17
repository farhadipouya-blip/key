<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    protected $fillable = [
        'exam_id', 'user_id', 'attempt_number', 'started_at', 'finished_at', 'status',
        'score', 'total_score', 'percent', 'correct_count', 'wrong_count', 'blank_count',
        'time_spent_seconds',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class, 'attempt_id');
    }

    /** زمان باقی‌مانده بر حسب ثانیه */
    public function remainingSeconds(): ?int
    {
        if (! $this->exam->duration_minutes) {
            return null;
        }
        $limit = $this->started_at->copy()->addMinutes($this->exam->duration_minutes);
        return max(0, (int) now()->diffInSeconds($limit, false));
    }

    public function isExpired(): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }
        if (! $this->exam->duration_minutes) {
            return false;
        }
        return $this->remainingSeconds() === 0;
    }
}
