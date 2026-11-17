<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankEstimate extends Model
{
    protected $fillable = [
        'user_id', 'exam_attempt_id', 'input', 'estimated_rank', 'percentile', 'scenario',
    ];

    protected function casts(): array
    {
        return ['input' => 'array', 'scenario' => 'array', 'percentile' => 'float'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
