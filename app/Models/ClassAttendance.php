<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * حضور و غیاب کلاس آنلاین
 */
class ClassAttendance extends Model
{
    protected $fillable = [
        'class_id', 'user_id', 'joined_at', 'left_at', 'status',
    ];

    protected function casts(): array
    {
        return ['joined_at' => 'datetime', 'left_at' => 'datetime'];
    }

    public function onlineClass(): BelongsTo
    {
        return $this->belongsTo(OnlineClass::class, 'class_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
