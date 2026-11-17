<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * مخزن تنظیمات سامانه: خواندن/نوشتن تنظیم‌ها با کش خودکار
 */
class SettingsRepository
{
    private ?array $cache = null;

    public function get(string $key): mixed
    {
        return $this->all()[$key] ?? null;
    }

    public function all(): array
    {
        if ($this->cache === null) {
            $this->cache = Cache::rememberForever('key.settings', fn () => Setting::pluck('value', 'key')->all());
        }
        return $this->cache;
    }

    public function set(string $key, mixed $value, string $type = 'text', string $group = 'general', ?string $label = null): void
    {
        Setting::updateOrCreate(['key' => $key], [
            'value' => $value,
            'type' => $type,
            'group' => $group,
            'label' => $label,
        ]);
        $this->flush();
    }

    public function flush(): void
    {
        $this->cache = null;
        Cache::forget('key.settings');
    }
}
