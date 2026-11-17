<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Support\SettingsRepository;
use Illuminate\Http\Request;

/**
 * تنظیمات سامانه — فرم گروه‌بندی‌شده با ماسک کردن کلیدهای API
 */
class AdminSettingController extends Controller
{
    /** عنوان فارسی گروه‌های تنظیمات */
    public const GROUP_TITLES = [
        'general' => 'عمومی',
        'sms' => 'پیامک',
        'commerce' => 'فروش و کش‌بک',
        'payments' => 'پرداخت',
        'ai' => 'هوش مصنوعی',
        'classes' => 'کلاس آنلاین',
        'learning' => 'یادگیری',
    ];

    /** الگوهای کلیدهای حساس که مقدارشان ماسک می‌شود */
    private const SECRET_PATTERNS = ['key', 'secret', 'password', 'merchant', 'token'];

    public function index()
    {
        $settings = Setting::query()->orderBy('key')->get();

        $groups = [];
        foreach ($settings as $setting) {
            $groups[$setting->group][] = [
                'key' => $setting->key,
                'value' => $setting->value,
                'type' => $setting->type,
                'label' => $setting->label ?: $setting->key,
                'secret' => $this->isSecret($setting->key),
            ];
        }

        $groupTitles = self::GROUP_TITLES;
        foreach ($groups as $group => $items) {
            $groupTitles[$group] ??= $group;
        }

        return view('admin.settings.index', compact('groups', 'groupTitles'));
    }

    public function save(Request $request, SettingsRepository $repo)
    {
        $settings = Setting::query()->get(['key', 'type', 'group', 'label']);

        $saved = 0;
        foreach ($settings as $setting) {
            $input = $request->input('settings.' . $setting->key);

            // کلید حساس: اگر مقدار ماسک‌شده برگشته یا خالی ارسال شده، دست نزن
            if ($this->isSecret($setting->key)) {
                if ($input === null || str_contains((string) $input, '••')) {
                    continue;
                }
            }

            if ($input === null) {
                continue;
            }

            $value = $setting->type === 'bool' ? ($request->boolean('settings.' . $setting->key) ? '1' : '0') : trim((string) $input);

            setting_set($setting->key, $value, $setting->type, $setting->group, $setting->label);
            $saved++;
        }

        $repo->flush();
        AuditLog::record($request->user(), 'admin.settings.saved', null, ['count' => $saved]);

        return back()->with('success', 'تنظیمات با موفقیت ذخیره شد (' . $saved . ' مورد).');
    }

    /** آیا کلید از نوع حساس (API/رمز) است؟ */
    private function isSecret(string $key): bool
    {
        foreach (self::SECRET_PATTERNS as $pattern) {
            if (str_contains($key, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
