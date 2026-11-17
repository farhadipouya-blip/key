<?php

namespace App\Providers;

use App\Models\Setting;
use App\Support\SettingsRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRepository::class, function () {
            return new SettingsRepository();
        });
    }

    public function boot(): void
    {
        // دیسک storage لینک شود اگر نبود (محیط تولید)
        if (config('filesystems.default') === 'local' && ! is_link(public_path('storage'))) {
            try {
                @symlink(storage_path('app/public'), public_path('storage'));
            } catch (\Throwable) {
                // روی هاست اشتراکی ممکن است مجوز symlink نباشد؛ artisan storage:link اجرا شود
            }
        }

        // Gate عمومی برای کنترل مجوز ماژول‌ها: @can('access', 'exams.manage')
        Gate::define('access', function ($user, string $permission) {
            return $user->canAccess($permission);
        });

        // مدیر کل به همه چیز دسترسی دارد؛ مجوزهای ماژولی (مثل tickets.view)
        // مستقیماً از RBAC کاربر بررسی می‌شوند
        Gate::before(function ($user, string $ability) {
            if ($user->hasRole('superadmin')) {
                return true;
            }

            if (\App\Models\Permission::query()->where('slug', $ability)->exists()) {
                return $user->canAccess($ability) ? true : false;
            }

            return null; // سایر توانمندی‌ها به Policy ها واگذار می‌شود
        });
    }
}
