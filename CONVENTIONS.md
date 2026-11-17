# قراردادهای کدنویسی سامانه کلید (KEY)

این سند برای همه توسعه‌دهندگان/عامل‌های کدنویسی این پروژه الزامی است.

## محیط اجرا
- PHP ایستا: `/home/z/my-project/runtime/php/php` — همیشه با `export PATH=/home/z/my-project/runtime/php:$PATH` قبل از artisan.
- MariaDB: اجرا با `bash /home/z/my-project/scripts/dev-up.sh` (اگر از بین رفت). سوکت: `/home/z/my-project/key-data/mysql.sock` — اتصال TCP: 127.0.0.1:3306.
- پروژه: `/home/z/my-project/key` — Laravel 12، MySQL با نام keydb، کاربر keyuser / "KeyLocal#2026".
- سرور آزمایشی: `php artisan serve --host=127.0.0.1 --port=8000` (معمولاً در حال اجراست؛ اگر نبود اجرا کن).
- Composer: `php ../runtime/composer.phar <cmd>` از داخل پوشه key.

## قوانین الزامی
1. **فقط فایل‌های ماژول خودت را بساز/ویرایش کن.** فایل route اختصاصی تو: `routes/modules/<module>.php`. از ویرایش فایل‌های دیگران خودداری کن.
2. **مایگریت جدید ممنوع** — همه جداول از قبل ساخته شده‌اند. مدل‌ها در `app/Models/*.php` هستند؛ فیلدها را از فایل مایگریت مرتبط بخوان.
3. **رابط کاربری کاملاً فارسی و RTL**، اعداد فارسی با `fa_num()`, تاریخ با `fa_date($x)` / `fa_datetime($x)`, مبلغ با `money($x)`.
4. از کامپوننت‌های Blade موجود استفاده کن:
   - `<x-card title="..." icon="bi-..." actions="<a ...>...</a>">محتوا</x-card>`
   - `<x-stat icon="bi-..." label="..." value="{{ fa_num($x) }}" color="primary|success|danger|warning|info" url="..."/>`
   - `<x-badge :status="$s" :labels="['open'=>'باز','closed'=>'بسته']"/>`
   - `<x-empty icon="bi-..." title="..." message="..." actionLabel="..." actionUrl="..."/>`
   - فرم‌ها: `<x-input name="x" label="..."/>`, `<x-select name="x" :options="[$k=>$v]" :value="$old"/>`, `<x-textarea .../>`, `<x-switch name="x" label="..."/>`
   - هشدارها: `@include('components.alerts')` (در لایه‌بندی خودکار هست)
5. لایه‌بندی پنل: `@extends('layouts.app')` با `@section('page_title', '...')`. صفحه عمومی: `@extends('layouts.public')`.
6. **middleware مجوز**: `->middleware('permission:exams.manage')` — نام مجوزها را از `database/seeders/RolePermissionSeeder.php` بخوان و دقیقاً همان را استفاده کن.
7. **نام مسیرها (route names) باید دقیقاً مطابق `config/menu.php` باشد** چون منوی سایدبار و داشبوردها به آن‌ها ارجاع می‌دهند. برای لینک‌های کمکی از `route_if('name', $params)` استفاده کن (اگر مسیر نبود، `#` می‌دهد و صفحه نمی‌شکند).
8. کنترلرها: `App\Http\Controllers\<Name>Controller.php` (namespace موجود). Validation با `$request->validate` و پیام‌های فارسی.
9. **امنیت**: همه عملیات حساس `\App\Models\AuditLog::record(auth()->user(), 'action.name', $model, $payload)`. کنترل مالکیت رکورد در سمت سرور (`if ($model->user_id !== auth()->id()) abort(403);`). اعتبارسنجی همه ورودی‌ها.
10. **درست‌بودن عملکرد**: فرم‌ها واقعاً ذخیره کنند؛ دکمه‌ها واقعاً کار کنند؛ داده در MySQL ذخیره شود. هیچ داده ساختگی در ویو.
11. اعلان‌ها: از Laravel Notification با کانال database استفاده کن (`$user->notify(new XNotification(...))`) و کلاس‌ها را در `app/Notifications` بساز با فیلد‌های array: title, body, url, icon.
12. بعد از اتمام: `php artisan view:clear`، صفحات را با curl تست کن (ورود با حساب‌های آزمایشی) و خطاها را از `storage/logs/laravel.log` برطرف کن.
13. تست Feature بنویس در `tests/Feature/<Module>Test.php` و اجرا: `php artisan test --filter=<Module>`.
14. از node/npm و vite استفاده نکن — دارایی‌ها استاتیک در `public/assets`.

## حساب‌های آزمایشی (رمز همه: Admin@1234)
- admin@key.test (مدیر کل) / expert@key.test (کارشناس) / counselor@key.test (مشاور)
- teacher@key.test (مدرس) / content@key.test (تولیدکننده محتوا) / student@key.test (دانشجو)

## ساختار داده کلیدی (خلاصه)
- users (فیلدهای اضافه: phone, grade, field_group, is_active, last_login_at, last_seen_at) — roles() و hasRole(), canAccess($slug), isStaff(), isAdmin(), walletBalance(), hasActiveSubscription()
- subjects (درخت root/chapter/lesson) — questions + question_options (order 1..n, is_correct)
- exams (status published/draft/closed, is_public, duration_minutes, negative_marking) — exam_questions — exam_attempts (status in_progress/finished) — attempt_answers (attempt_id, selected_option_id, is_correct nullable tinyint, score, flagged)
- reviews (user_id, reviewable morph: Question|Summary, easiness, interval_days, due_at) — review_logs
- summaries (user_id null=عمومی, category, is_public) — bookmarks (morph)
- study_plans — plan_items (date, target_minutes, completed_minutes, status) — study_sessions (date, minutes) — goals — mastery_levels
- categories (type course|article) — courses — lessons (type video|text|pdf) — course_enrollments (progress) — lesson_progress
- articles — comments (morph)
- online_classes (platform skyroom/alokam, join_url, starts_at) — class_attendances
- assignments — assignment_submissions (file_path, score, feedback, graded_by)
- certificates (uuid, data json, revoked_at) — pdf_reads — highlights
- subscription_plans — subscriptions — coupons (type percent/fixed, discountFor($amount)) — products — orders — order_items (morph purchasable: course|plan|product) — transactions — wallet_transactions
- tickets (status open/answered/pending/closed, priority) — ticket_messages
- chat_rooms (type private/group/class) — chat_room_user — chat_messages (type text/file/image/audio/poll, poll_options json) — poll_votes
- sms_messages — media + media_folders — file_imports — universities — majors — major_university (last_rank_min/max, tuition_type, chancePercent($rank)) — rank_estimates — field_selections — historical_results — counselor_student (status) — counseling_sessions — follow_ups — audit_logs — settings — fonts — ai_conversations — ai_messages

## سرویس‌های آماده (از آن‌ها استفاده کن، دوباره نساز)
- `App\Services\ExamService` (grade, buildFromSubjects, optionAnalysis)
- `App\Services\ReviewService` (ensureReview, dailyQueue, pendingCount, schedule, syncFromExam, retentionStats)
- `App\Services\CommerceService` (createOrder, markPaid, grantSubscription, addToWallet, activatePurchase)
- `App\Services\CourseEnrollmentService::enroll($user, $course, $source, $orderId)` و `recomputeProgress`
- `App\Services\MasteryService` (updateFromAttempt, recomputeSubject, strengthsAndWeaknesses)
- `App\Services\RankService` (estimate($user, $input, $group), selectionAnalysis($rank, $group, $year))
- `App\Services\SmsService` (send($phone, $body, $userId, $template))
- `App\Services\AiService` (available, chat($messages), suggestTicketReply, analyzePerformance)
- `App\Http\Controllers\CertificateController::issue($user, $title, $course, $attemptId, $type, $issuedBy)` و `qrSvg($url)`

## دیباگ
- لاگ: `storage/logs/laravel.log` (آخرین خطا: `grep "local.ERROR" storage/logs/laravel.log | tail -1`)
- نکته: مقایسه `is_correct` پایگاه داده به‌صورت int می‌آید؛ از truthy check استفاده کن نه `=== false`.
