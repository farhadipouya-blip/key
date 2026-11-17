<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Support\SettingsRepository;

/**
 * تنظیمات پیش‌فرض سامانه
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $repo = app(SettingsRepository::class);

        $defaults = [
            // کلید | مقدار | نوع | گروه | برچسب
            ['site.site_name', 'کلید', 'text', 'general', 'نام سامانه'],
            ['site.support_phone', '021-91000000', 'text', 'general', 'تلفن پشتیبانی'],
            ['site.site_email', 'support@key.local', 'text', 'general', 'ایمیل پشتیبانی'],

            ['sms.driver', 'log', 'text', 'sms', 'درایور پیامک (log/kavenegar/smsir/mellipayamak/faraz)'],
            ['sms.sender_number', '', 'text', 'sms', 'شماره ارسال‌کننده'],
            ['sms.kavenegar_key', '', 'text', 'sms', 'کلید کاوه‌نگار'],
            ['sms.smsir_key', '', 'text', 'sms', 'کلید SMS.ir'],
            ['sms.smsir_secret', '', 'text', 'sms', 'کلید امنیتی SMS.ir'],
            ['sms.melli_username', '', 'text', 'sms', 'نام کاربری ملی‌پیامک'],
            ['sms.melli_password', '', 'text', 'sms', 'رمز ملی‌پیامک'],
            ['sms.faraz_key', '', 'text', 'sms', 'کلید فراز اس‌ام‌اس'],

            ['commerce.cashback_percent', '5', 'number', 'commerce', 'درصد کش‌بک خریدها'],
            ['commerce.currency', 'تومان', 'text', 'commerce', 'واحد پول'],

            ['payments.gateway', 'mock', 'text', 'payments', 'درگاه پرداخت فعال (mock/zarinpal)'],
            ['payments.zarinpal_merchant', '', 'text', 'payments', 'شناسه پذیرندگی زرین‌پال'],

            ['ai.provider', 'none', 'text', 'ai', 'سرویس هوش مصنوعی (none/openai/gemini)'],
            ['ai.openai_key', '', 'text', 'ai', 'کلید OpenAI'],
            ['ai.gemini_key', '', 'text', 'ai', 'کلید Google Gemini'],
            ['ai.model', 'gpt-4o-mini', 'text', 'ai', 'نام مدل'],

            ['classes.skyroom_url', '', 'text', 'classes', 'آدرس سامانه اسکای‌روم'],
            ['classes.skyroom_api_key', '', 'text', 'classes', 'کلید API اسکای‌روم'],
            ['classes.alokam_url', '', 'text', 'classes', 'آدرس سامانه الوکام'],
            ['classes.alokam_api_key', '', 'text', 'classes', 'کلید API الوکام'],

            ['review.max_daily', '20', 'number', 'learning', 'حداکثر مرور روزانه'],
            ['plans.default_weekly_minutes', '600', 'number', 'learning', 'هدف هفتگی پیش‌فرض مطالعه (دقیقه)'],
        ];

        foreach ($defaults as [$key, $value, $type, $group, $label]) {
            $repo->set($key, $value, $type, $group, $label);
        }
    }
}
