<?php

use App\Support\Jalali;
use App\Support\SettingsRepository;

if (! function_exists('fa_num')) {
    /** تبدیل ارقام لاتین به فارسی */
    function fa_num(string|int|float|null $text): string
    {
        if ($text === null) {
            return '';
        }
        return Jalali::digits((string) $text);
    }
}

if (! function_exists('fa_date')) {
    /** نمایش تاریخ جلالی فارسی */
    function fa_date(?string $datetime, string $pattern = 'Y/m/d'): string
    {
        return Jalali::format($datetime, $pattern);
    }
}

if (! function_exists('fa_datetime')) {
    /** نمایش تاریخ و ساعت جلالی فارسی */
    function fa_datetime(?string $datetime): string
    {
        return Jalali::format($datetime, 'Y/m/d — H:i');
    }
}

if (! function_exists('money')) {
    /** قالب‌بندی مبلغ به تومان */
    function money(int|float|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }
        return fa_num(number_format((float) $amount)) . ' تومان';
    }
}

if (! function_exists('percent')) {
    /** محاسبه درصد ایمن (تقسیم بر صفر = صفر) */
    function percent(int|float $part, int|float $total, int $precision = 1): float
    {
        if ($total <= 0) {
            return 0.0;
        }
        return round(($part / $total) * 100, $precision);
    }
}

if (! function_exists('duration_human')) {
    /** تبدیل دقیقه به متن خوانا (ساعت و دقیقه) */
    function duration_human(int|float|null $minutes): string
    {
        $minutes = (int) round((float) $minutes);
        if ($minutes <= 0) {
            return '۰ دقیقه';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        if ($h > 0 && $m > 0) {
            return fa_num($h) . ' ساعت و ' . fa_num($m) . ' دقیقه';
        }
        if ($h > 0) {
            return fa_num($h) . ' ساعت';
        }
        return fa_num($m) . ' دقیقه';
    }
}

if (! function_exists('setting')) {
    /** خواندن تنظیم سامانه (پایگاه داده با کش) */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingsRepository::class)->get($key) ?? $default;
    }
}

if (! function_exists('setting_set')) {
    /** ثبت تنظیم سامانه */
    function setting_set(string $key, mixed $value, string $type = 'text', string $group = 'general', ?string $label = null): void
    {
        app(SettingsRepository::class)->set($key, $value, $type, $group, $label);
    }
}

if (! function_exists('route_if')) {
    /** تولید URL مسیر در صورت وجود؛ در غیر اینصورت لینک غیرفعال (#) */
    function route_if(string $name, $params = []): string
    {
        try {
            if (\Illuminate\Support\Facades\Route::has($name)) {
                return route($name, $params);
            }
        } catch (\Throwable) {
        }
        return '#';
    }
}

if (! function_exists('storage_public_url')) {
    /** آدرس فایل ذخیره‌شده در دیسک public */
    function storage_public_url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        return asset('storage/' . ltrim($path, '/'));
    }
}
