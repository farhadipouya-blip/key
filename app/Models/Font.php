<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Font extends Model
{
    protected $fillable = [
        'name', 'family', 'slug', 'weights', 'file_path', 'format',
        'license_name', 'license_note', 'status', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'weights' => 'array',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** CSS فونت‌های نصب‌شده برای لایه‌بندی */
    public static function installedCss(): string
    {
        $css = '';
        foreach (static::query()->where('status', 'installed')->get() as $font) {
            $weights = $font->weights ?: [400];
            foreach ((array) $weights as $w) {
                $css .= "@font-face { font-family: '{$font->family}'; src: url('{$font->file_path}') format('{$font->format}'); font-weight: {$w}; font-display: swap; }\n";
            }
        }
        return $css;
    }
}
