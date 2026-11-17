<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label'];

    /** خواندن مقدار تنظیم با کش */
    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = Cache::rememberForever('key.settings', fn () => static::pluck('value', 'key')->all());

        if (! array_key_exists($key, $settings) || $settings[$key] === null || $settings[$key] === '') {
            return $default;
        }

        return $settings[$key];
    }

    public static function set(string $key, mixed $value, string $type = 'text', string $group = 'general', ?string $label = null): void
    {
        static::updateOrCreate(['key' => $key], [
            'value' => $value,
            'type' => $type,
            'group' => $group,
            'label' => $label,
        ]);
        Cache::forget('key.settings');
    }
}
