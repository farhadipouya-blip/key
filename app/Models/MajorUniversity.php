<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MajorUniversity extends Model
{
    protected $table = 'major_university';

    protected $fillable = [
        'university_id', 'major_id', 'year', 'capacity',
        'last_rank_min', 'last_rank_max', 'tuition_type',
    ];

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    /** احتمال قبولی برای یک رتبه تخمینی (0 تا 100) */
    public function chancePercent(?int $estimatedRank): int
    {
        if (! $estimatedRank || ! $this->last_rank_max) {
            return 0;
        }
        if ($estimatedRank <= (int) $this->last_rank_min) {
            return 95; // بسیار بهتر از آخرین قبولی
        }
        if ($estimatedRank <= (int) $this->last_rank_max) {
            // داخل بازه حدنصاب: احتمال متوسط با نزول خطی
            $span = (int) $this->last_rank_max - (int) $this->last_rank_min;
            $depth = $estimatedRank - (int) $this->last_rank_min;
            return (int) max(10, round(75 - (75 * $depth / max(1, $span))));
        }
        // بدتر از آخرین قبولی
        $gap = $estimatedRank - (int) $this->last_rank_max;
        return (int) max(0, round(15 - ($gap / max(500, $this->last_rank_max) * 15)));
    }
}
