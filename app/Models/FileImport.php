<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileImport extends Model
{
    protected $fillable = [
        'user_id', 'type', 'file_path', 'original_name', 'status',
        'total_rows', 'success_rows', 'error_rows', 'errors', 'stats', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
            'stats' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
