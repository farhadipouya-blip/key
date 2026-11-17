<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'user_id', 'folder_id', 'name', 'file_name', 'path', 'disk', 'mime_type',
        'size', 'type', 'access', 'meta', 'is_scanned', 'scan_result', 'downloads',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array', 'is_scanned' => 'boolean', 'size' => 'int'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(PdfRead::class, 'media_id');
    }

    public function highlights(): HasMany
    {
        return $this->hasMany(Highlight::class, 'media_id');
    }

    public function humanSize(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' مگابایت';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' کیلوبایت';
        }
        return $bytes . ' بایت';
    }

    public static function typeFromMime(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            $mime === 'application/pdf' => 'pdf',
            str_contains($mime, 'word') || str_contains($mime, 'document') => 'document',
            str_contains($mime, 'spreadsheet') || str_contains($mime, 'excel') => 'document',
            str_contains($mime, 'zip') || str_contains($mime, 'compressed') => 'archive',
            default => 'other',
        };
    }
}
