<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = [
        'user_id', 'title', 'course_id', 'exam_attempt_id', 'type', 'uuid',
        'data', 'issued_by', 'issued_at', 'revoked_at', 'template',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** کاربری که گواهی‌نامه را صادر کرده است */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public static function generateUuid(): string
    {
        return bin2hex(random_bytes(16));
    }
}
