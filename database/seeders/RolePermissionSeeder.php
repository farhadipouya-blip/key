<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * نقش‌های هفت‌گانه و مجوزهای ماژول‌ها
 */
class RolePermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        // [slug, name, group]
        ['exams.take', 'شرکت در آزمون‌ها', 'آزمون'],
        ['exams.manage', 'مدیریت آزمون‌ها', 'آزمون'],
        ['questions.manage', 'مدیریت بانک سؤال', 'آزمون'],
        ['subjects.manage', 'مدیریت دروس و مباحث', 'آزمون'],
        ['reviews.view', 'استفاده از مرور هوشمند', 'یادگیری'],
        ['summaries.view', 'مشاهده بانک خلاصه', 'یادگیری'],
        ['summaries.manage', 'مدیریت خلاصه‌های عمومی', 'یادگیری'],
        ['plans.view', 'استفاده از برنامه مطالعاتی', 'یادگیری'],
        ['analytics.view', 'تحلیل عملکرد', 'یادگیری'],
        ['rank.view', 'تخمین رتبه و انتخاب رشته', 'یادگیری'],
        ['certificates.view', 'مشاهده گواهینامه‌ها', 'آکادمی'],
        ['certificates.issue', 'صدور و مدیریت گواهینامه', 'آکادمی'],
        ['academy.learn', 'استفاده از آکادمی', 'آکادمی'],
        ['academy.manage', 'مدیریت آکادمی (دوره/مقاله/کلاس/تکلیف)', 'آکادمی'],
        ['academy.teach', 'تدریس و تصحیح', 'آکادمی'],
        ['shop.buy', 'خرید از فروشگاه', 'تجارت'],
        ['shop.manage', 'مدیریت فروش و اشتراک', 'تجارت'],
        ['chat.use', 'استفاده از چت', 'ارتباطات'],
        ['tickets.view', 'ثبت و مشاهده تیکت', 'ارتباطات'],
        ['tickets.support', 'پاسخگویی تیکت‌ها', 'ارتباطات'],
        ['counselor.panel', 'پنل مشاور', 'مشاوره'],
        ['monitoring.view', 'پایش سامانه', 'مدیریت'],
        ['users.manage', 'مدیریت کاربران', 'مدیریت'],
        ['roles.manage', 'مدیریت نقش‌ها', 'مدیریت'],
        ['settings.manage', 'تنظیمات سامانه', 'مدیریت'],
        ['audit.view', 'مشاهده لاگ فعالیت', 'مدیریت'],
        ['media.manage', 'مدیریت رسانه', 'مدیریت'],
        ['fonts.manage', 'مدیریت فونت', 'مدیریت'],
        ['imports.manage', 'ورود گروهی اطلاعات', 'مدیریت'],
        ['ai.use', 'دستیار هوش مصنوعی', 'متفرقه'],
    ];

    public const ROLES = [
        ['slug' => 'superadmin', 'name' => 'مدیر کل', 'color' => '#7c3aed'],
        ['slug' => 'admin', 'name' => 'مدیر', 'color' => '#2f5bea'],
        ['slug' => 'expert', 'name' => 'کارشناس', 'color' => '#0891b2'],
        ['slug' => 'counselor', 'name' => 'مشاور', 'color' => '#16a34a'],
        ['slug' => 'teacher', 'name' => 'مدرس', 'color' => '#d97706'],
        ['slug' => 'content_creator', 'name' => 'تولیدکننده محتوا', 'color' => '#9333ea'],
        ['slug' => 'student', 'name' => 'دانشجو', 'color' => '#6b7590'],
    ];

    public const ROLE_PERMISSIONS = [
        'superadmin' => '*',
        'admin' => '*',
        'expert' => ['monitoring.view', 'audit.view', 'tickets.support', 'tickets.view', 'imports.manage', 'media.manage', 'chat.use', 'counselor.panel'],
        'counselor' => ['counselor.panel', 'tickets.support', 'tickets.view', 'analytics.view', 'chat.use', 'rank.view', 'certificates.view', 'monitoring.view', 'ai.use'],
        'teacher' => ['exams.manage', 'questions.manage', 'subjects.manage', 'academy.teach', 'academy.learn', 'exams.take', 'reviews.view', 'media.manage', 'chat.use', 'tickets.view'],
        'content_creator' => ['questions.manage', 'subjects.manage', 'summaries.manage', 'academy.manage', 'media.manage', 'chat.use', 'tickets.view'],
        'student' => ['exams.take', 'reviews.view', 'summaries.view', 'plans.view', 'analytics.view', 'rank.view', 'academy.learn', 'shop.buy', 'chat.use', 'tickets.view', 'certificates.view', 'ai.use'],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as [$slug, $name, $group]) {
            Permission::updateOrCreate(['slug' => $slug], ['name' => $name, 'group' => $group]);
        }

        foreach (self::ROLES as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }

        $allPermissions = Permission::pluck('id', 'slug');

        foreach (self::ROLE_PERMISSIONS as $roleSlug => $slugs) {
            $role = Role::where('slug', $roleSlug)->first();
            if (! $role) {
                continue;
            }
            $ids = $slugs === '*'
                ? $allPermissions->values()
                : collect($slugs)->map(fn ($s) => $allPermissions[$s]);

            $role->permissions()->sync($ids->all());
        }
    }
}
