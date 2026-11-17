<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewLog extends Model
{
    public const UPDATED_AT = null;
    public const CREATED_AT = null;

    protected $fillable = [
        'review_id', 'user_id', 'quality', 'interval_before',
        'interval_after', 'easiness_after', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
