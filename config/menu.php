<?php

/*
|--------------------------------------------------------------------------
| منوی سامانه کلید — نقش‌محور
|--------------------------------------------------------------------------
| هر آیتم: label، icon، route، permission (اختیاری)، role (اختیاری)
| آیتم‌هایی که permission دارند فقط برای دارندگان مجوز نمایش داده می‌شوند.
| گروه‌ها به تفکیک بخش سامانه تعریف شده‌اند.
*/

return [
    [
        'section' => 'اصلی',
        'items' => [
            ['label' => 'داشبورد', 'icon' => 'bi-grid-1x2', 'route' => 'dashboard'],
        ],
    ],
    [
        'section' => 'یادگیری',
        'items' => [
            ['label' => 'بانک تست و آزمون', 'icon' => 'bi-journal-check', 'route' => 'exams.hub', 'permission' => 'exams.take'],
            ['label' => 'مرور هوشمند', 'icon' => 'bi-arrow-repeat', 'route' => 'review.index', 'permission' => 'reviews.view'],
            ['label' => 'برنامه مطالعاتی', 'icon' => 'bi-calendar2-week', 'route' => 'plan.index', 'permission' => 'plans.view'],
            ['label' => 'بانک خلاصه و نکته', 'icon' => 'bi-collection', 'route' => 'summaries.index', 'permission' => 'summaries.view'],
            ['label' => 'تحلیل عملکرد', 'icon' => 'bi-graph-up-arrow', 'route' => 'analytics.index', 'permission' => 'analytics.view'],
            ['label' => 'تخمین رتبه و انتخاب رشته', 'icon' => 'bi-trophy', 'route' => 'rank.index', 'permission' => 'rank.view'],
            ['label' => 'گواهی‌نامه‌های من', 'icon' => 'bi-patch-check', 'route' => 'certificates.index', 'permission' => 'certificates.view'],
        ],
    ],
    [
        'section' => 'آکادمی',
        'items' => [
            ['label' => 'دوره‌ها', 'icon' => 'bi-mortarboard', 'route' => 'academy.courses.index', 'permission' => 'academy.learn'],
            ['label' => 'دوره‌های من', 'icon' => 'bi-book', 'route' => 'academy.my-courses', 'permission' => 'academy.learn'],
            ['label' => 'کلاس‌های آنلاین', 'icon' => 'bi-camera-video', 'route' => 'academy.classes', 'permission' => 'academy.learn'],
            ['label' => 'تکالیف من', 'icon' => 'bi-clipboard-check', 'route' => 'academy.my-assignments', 'permission' => 'academy.learn'],
            ['label' => 'مطالعه PDF', 'icon' => 'bi-file-earmark-pdf', 'route' => 'pdf.index', 'permission' => 'academy.learn'],
            ['label' => 'مقالات آموزشی', 'icon' => 'bi-newspaper', 'route' => 'articles.public.index', 'role' => null],
        ],
    ],
    [
        'section' => 'فروشگاه',
        'items' => [
            ['label' => 'فروشگاه و اشتراک', 'icon' => 'bi-bag', 'route' => 'shop.index', 'permission' => 'shop.buy'],
            ['label' => 'سبد خرید', 'icon' => 'bi-cart', 'route' => 'shop.cart', 'permission' => 'shop.buy'],
            ['label' => 'سفارش‌های من', 'icon' => 'bi-receipt', 'route' => 'shop.orders', 'permission' => 'shop.buy'],
            ['label' => 'کیف پول', 'icon' => 'bi-wallet2', 'route' => 'shop.wallet', 'permission' => 'shop.buy'],
        ],
    ],
    [
        'section' => 'ارتباطات',
        'items' => [
            ['label' => 'گفتگو (چت)', 'icon' => 'bi-chat-dots', 'route' => 'chat.index', 'permission' => 'chat.use'],
            ['label' => 'تیکت پشتیبانی', 'icon' => 'bi-life-preserver', 'route' => 'tickets.index', 'permission' => 'tickets.view'],
        ],
    ],
    [
        'section' => 'پنل مشاور',
        'items' => [
            ['label' => 'دانشجویان من', 'icon' => 'bi-people', 'route' => 'counselor.dashboard', 'permission' => 'counselor.panel'],
            ['label' => 'جلسات مشاوره', 'icon' => 'bi-calendar-heart', 'route' => 'counselor.sessions', 'permission' => 'counselor.panel'],
            ['label' => 'پیگیری‌ها', 'icon' => 'bi-arrow-left-right', 'route' => 'counselor.followups', 'permission' => 'counselor.panel'],
        ],
    ],
    [
        'section' => 'پنل مدرس',
        'items' => [
            ['label' => 'دوره‌های تدریس من', 'icon' => 'bi-easel', 'route' => 'teacher.courses', 'permission' => 'academy.teach'],
            ['label' => 'تصحیح تکالیف', 'icon' => 'bi-pencil-square', 'route' => 'teacher.assignments', 'permission' => 'academy.teach'],
            ['label' => 'کلاس‌های من', 'icon' => 'bi-camera-video', 'route' => 'teacher.classes', 'permission' => 'academy.teach'],
            ['label' => 'دفتر نمره', 'icon' => 'bi-table', 'route' => 'teacher.gradebook', 'permission' => 'academy.teach'],
        ],
    ],
    [
        'section' => 'مدیریت سامانه',
        'items' => [
            ['label' => 'داشبورد مدیریت', 'icon' => 'bi-speedometer2', 'route' => 'admin.dashboard', 'permission' => 'monitoring.view'],
            ['label' => 'پایش سامانه', 'icon' => 'bi-activity', 'route' => 'admin.monitoring', 'permission' => 'monitoring.view'],
            ['label' => 'کاربران', 'icon' => 'bi-person-gear', 'route' => 'admin.users.index', 'permission' => 'users.manage'],
            ['label' => 'نقش‌ها و مجوزها', 'icon' => 'bi-shield-lock', 'route' => 'admin.roles.index', 'permission' => 'roles.manage'],
            ['label' => 'درس و مبحث', 'icon' => 'bi-diagram-3', 'route' => 'admin.subjects.index', 'permission' => 'subjects.manage'],
            ['label' => 'بانک سؤال', 'icon' => 'bi-database', 'route' => 'admin.questions.index', 'permission' => 'questions.manage'],
            ['label' => 'آزمون‌ساز', 'icon' => 'bi-ui-checks', 'route' => 'admin.exams.index', 'permission' => 'exams.manage'],
            ['label' => 'خلاصه‌های عمومی', 'icon' => 'bi-file-earmark-text', 'route' => 'admin.summaries.index', 'permission' => 'summaries.manage'],
            ['label' => 'مدیریت دوره‌ها', 'icon' => 'bi-mortarboard-fill', 'route' => 'admin.courses.index', 'permission' => 'academy.manage'],
            ['label' => 'مدیریت مقالات', 'icon' => 'bi-newspaper', 'route' => 'admin.articles.index', 'permission' => 'academy.manage'],
            ['label' => 'کلاس‌های آنلاین', 'icon' => 'bi-camera-video', 'route' => 'admin.classes.index', 'permission' => 'academy.manage'],
            ['label' => 'تکالیف', 'icon' => 'bi-clipboard-check', 'route' => 'admin.assignments.index', 'permission' => 'academy.manage'],
            ['label' => 'گواهی‌نامه‌ها', 'icon' => 'bi-patch-check', 'route' => 'admin.certificates.index', 'permission' => 'certificates.issue'],
            ['label' => 'اشتراک‌ها', 'icon' => 'bi-star', 'route' => 'admin.plans.index', 'permission' => 'shop.manage'],
            ['label' => 'سفارش‌ها و پرداخت‌ها', 'icon' => 'bi-receipt-cutoff', 'route' => 'admin.orders.index', 'permission' => 'shop.manage'],
            ['label' => 'کوپن‌ها', 'icon' => 'bi-ticket-perforated', 'route' => 'admin.coupons.index', 'permission' => 'shop.manage'],
            ['label' => 'محصولات', 'icon' => 'bi-box-seam', 'route' => 'admin.products.index', 'permission' => 'shop.manage'],
            ['label' => 'تیکت‌های پشتیبانی', 'icon' => 'bi-headset', 'route' => 'support.tickets.index', 'permission' => 'tickets.support'],
            ['label' => 'کتابخانه رسانه', 'icon' => 'bi-collection-play', 'route' => 'media.index', 'permission' => 'media.manage'],
            ['label' => 'مدیریت فونت', 'icon' => 'bi-fonts', 'route' => 'fonts.index', 'permission' => 'fonts.manage'],
            ['label' => 'ورود گروهی اطلاعات', 'icon' => 'bi-filetype-xlsx', 'route' => 'imports.index', 'permission' => 'imports.manage'],
            ['label' => 'گزارش‌ها و لاگ', 'icon' => 'bi-list-check', 'route' => 'admin.audit', 'permission' => 'audit.view'],
            ['label' => 'تنظیمات سامانه', 'icon' => 'bi-gear', 'route' => 'admin.settings.index', 'permission' => 'settings.manage'],
            ['label' => 'هوش مصنوعی', 'icon' => 'bi-robot', 'route' => 'ai.index', 'permission' => 'ai.use'],
        ],
    ],
];
