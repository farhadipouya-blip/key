<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfRead extends Model
{
    // جدول pdf_reads ستون‌های created_at/updated_at ندارد؛ زمان مطالعه در last_read_at ذخیره می‌شود
    public $timestamps = false;

    protected $fillable = [
        'media_id', 'user_id', 'current_page', 'total_pages', 'progress', 'last_read_at',
    ];

    protected function casts(): array
    {
        return ['last_read_at' => 'datetime'];
    }
}
