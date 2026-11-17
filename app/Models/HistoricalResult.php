<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricalResult extends Model
{
    protected $fillable = [
        'user_id', 'year', 'exam_name', 'total_percent', 'rank',
        'subjects', 'source', 'file_import_id',
    ];

    protected function casts(): array
    {
        return ['subjects' => 'array', 'total_percent' => 'float'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
