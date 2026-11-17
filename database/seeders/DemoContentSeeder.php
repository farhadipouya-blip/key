<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Exam;
use App\Models\Goal;
use App\Models\HistoricalResult;
use App\Models\Lesson;
use App\Models\Major;
use App\Models\MajorUniversity;
use App\Models\PlanItem;
use App\Models\Question;
use App\Models\Role;
use App\Models\StudyPlan;
use App\Models\StudySession;
use App\Models\Subject;
use App\Models\Summary;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\University;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * محتوای آزمایشی واقعی برای تست و نمایش سامانه
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $pass = 'Admin@1234';

        // ------------------------------------------------------------------
        // کاربران آزمایشی
        // ------------------------------------------------------------------
        $admin = User::create(['name' => 'مدیر سامانه کلید', 'email' => 'admin@key.test', 'phone' => '09120000001', 'password' => Hash::make($pass), 'is_active' => true]);
        $expert = User::create(['name' => 'کارشناس آموزشی', 'email' => 'expert@key.test', 'phone' => '09120000002', 'password' => Hash::make($pass), 'is_active' => true]);
        $counselor = User::create(['name' => 'مشاور تحصیلی — خانم رضایی', 'email' => 'counselor@key.test', 'phone' => '09120000003', 'password' => Hash::make($pass), 'is_active' => true]);
        $teacher = User::create(['name' => 'مدرس — جناب موسوی', 'email' => 'teacher@key.test', 'phone' => '09120000004', 'password' => Hash::make($pass), 'is_active' => true]);
        $creator = User::create(['name' => 'تولیدکننده محتوا', 'email' => 'content@key.test', 'phone' => '09120000005', 'password' => Hash::make($pass), 'is_active' => true]);

        $student = User::create(['name' => 'دانشجو نمونه — علی محمدی', 'email' => 'student@key.test', 'phone' => '09120000010', 'password' => Hash::make($pass), 'is_active' => true, 'grade' => 'دوازدهم', 'field_group' => 'experimental', 'city' => 'تهران']);
        $student2 = User::create(['name' => 'دانشجو نمونه — سارا کریمی', 'email' => 'student2@key.test', 'phone' => '09120000011', 'password' => Hash::make($pass), 'is_active' => true, 'grade' => 'یازدهم', 'field_group' => 'math', 'city' => 'اصفهان']);
        $student3 = User::create(['name' => 'دانشجو نمونه — رضا احمدی', 'email' => 'student3@key.test', 'phone' => '09120000012', 'password' => Hash::make($pass), 'is_active' => true, 'grade' => 'فارغ‌التحصیل', 'field_group' => 'humanities', 'city' => 'شیراز']);

        $roleAssignments = [
            ['superadmin', $admin],
            ['expert', $expert],
            ['counselor', $counselor],
            ['teacher', $teacher],
            ['content_creator', $creator],
            ['student', $student],
            ['student', $student2],
            ['student', $student3],
        ];
        foreach ($roleAssignments as [$slug, $u]) {
            $role = Role::where('slug', $slug)->first();
            $u->roles()->syncWithoutDetaching([$role->id]);
        }

        // تخصیص دانشجو به مشاور
        \App\Models\CounselorStudent::create(['counselor_id' => $counselor->id, 'student_id' => $student->id, 'assigned_by' => $admin->id]);
        \App\Models\CounselorStudent::create(['counselor_id' => $counselor->id, 'student_id' => $student2->id, 'assigned_by' => $admin->id]);

        // ------------------------------------------------------------------
        // درخت دروس: درس > فصل > مبحث
        // ------------------------------------------------------------------
        $math = Subject::create(['title' => 'ریاضیات', 'level' => 'root', 'order' => 1]);
        $phys = Subject::create(['title' => 'فیزیک', 'level' => 'root', 'order' => 2]);
        $chem = Subject::create(['title' => 'شیمی', 'level' => 'root', 'order' => 3]);
        $bio = Subject::create(['title' => 'زیست‌شناسی', 'level' => 'root', 'order' => 4]);

        $mathCh1 = Subject::create(['title' => 'ماتریس و گویه‌ها', 'level' => 'chapter', 'parent_id' => $math->id]);
        $mathCh2 = Subject::create(['title' => 'توابع', 'level' => 'chapter', 'parent_id' => $math->id]);
        $mathCh2L1 = Subject::create(['title' => 'مفهوم تابع', 'level' => 'lesson', 'parent_id' => $mathCh2->id]);
        $mathCh1L1 = Subject::create(['title' => 'فراوانی و مود', 'level' => 'lesson', 'parent_id' => $mathCh1->id]);
        $physCh1 = Subject::create(['title' => 'حرکت‌شناسی', 'level' => 'chapter', 'parent_id' => $phys->id]);
        $chemCh1 = Subject::create(['title' => 'ساختار اتم', 'level' => 'chapter', 'parent_id' => $chem->id]);
        $bioCh1 = Subject::create(['title' => 'مولکول‌های زیستی', 'level' => 'chapter', 'parent_id' => $bio->id]);

        // ------------------------------------------------------------------
        // بانک سؤال (با فرمول LaTeX و گزینه‌ها)
        // ------------------------------------------------------------------
        $qData = [
            [$mathCh2L1, 'multi', 'easy', 'کدام‌یک تابع محسوب می‌شود؟', ['f(x) = ±x', 'f(x) = x^2', 'f(x) = |x| با دو مقدار', 'هیچ‌کدام'], 2, 'طبق تعریف تابع، هر x فقط یک y دارد؛ g(x) = x^2 تابع است.', 'ریاضی تجربی', 1402],
            [$mathCh1L1, 'multi', 'medium', 'میانه داده‌های ۲، ۴، ۶، ۸، ۱۰ کدام است؟', ['4', '5', '6', '8'], 3, 'میانه داده‌های پنج‌تایی = داده سوم = 6.', 'کارنامه سال قبل', 1401],
            [$mathCh2L1, 'multi', 'hard', 'اگر f(x) = 2x + 3 باشد، مقدار f(f(1)) چیست؟', ['7', '9', '11', '13'], 4, 'f(1)=5 و سپس f(5)=13', 'آزمون جامع کلید', 1403],
            [$physCh1, 'multi', 'medium', 'حرکت با سرعت ثابت و مسیر مستقیم را چه می‌نامند؟', ['حرکت شتاب‌دار', 'حرکت یکنواخت', 'حرکت دایره‌ای', 'حرکت نوسانی'], 2, 'در حرکت یکنواخت شتاب صفر است.', 'فیزیک دوازدهم', 1402],
            [$physCh1, 'multi', 'easy', 'واحد اندازه‌گیری شتاب در SI کدام است؟', ['m/s', 'm/s²', 'N', 'J'], 2, 'شتاب بر حسب متر بر مجذور ثانیه است.', 'فیزیک دهم', 1401],
            [$chemCh1, 'multi', 'medium', 'تعداد نوترون اتم $^{23}_{11}Na$ چند است؟', ['11', '12', '23', '34'], 2, 'نوترون = عدد جرمی − عدد اتمی = 23−11=12', 'شیمی دهم', 1402],
            [$chemCh1, 'multi', 'hard', 'کدام آرایش الکترونی پایدار (نوبل) است؟', ['1s²2s²2p⁴', '1s²2s²2p⁶', '1s²2s²', '1s²2s²2p³'], 2, 'آرایش p⁶ به پوسته کامل می‌رسد.', 'شیمی کنکور', 1403],
            [$bioCh1, 'multi', 'easy', 'واحد سازنده پروتئین‌ها چیست؟', ['نوکلئوتید', 'اسید آمینه', 'پلی‌ساکارید', 'اسید چرب'], 2, 'پروتئین‌ها از زنجیره اسیدهای آمینه ساخته می‌شوند.', 'زیست دهم', 1402],
            [$bioCh1, 'multi', 'medium', 'پیوند هیدروژنی در ساختار کدام مولکول حیاتی‌ترین نقش را دارد؟', ['چربی', 'DNA', 'نشاسته', 'گلیکوژن'], 2, 'پیوندهای هیدروژنی ساختار دوگانه DNA را تثبیت می‌کنند.', 'زیست یازدهم', 1401],
            [$mathCh1L1, 'multi', 'medium', 'در توزیع داده‌ها، مود عبارت است از:', ['میانگین داده‌ها', 'پرتکرارترین داده', 'داده وسط', 'تفاضل بیشینه و کمینه'], 2, 'مود = پرتکرارترین داده.', 'ریاضی نهم', 1400],
            [$mathCh2L1, 'multi', 'easy', 'دامنه تابع f(x) = \\sqrt{x-2} کدام است؟', ['x > 0', 'x ≥ 2', 'x > 2', 'همه اعداد'], 2, 'زیر رادیکال باید نامنفی باشد: x−2 ≥ 0', 'آزمون کلید', 1403],
            [$physCh1, 'multi', 'hard', 'اگر مسیر حرکت با رابطه x = 5t² باشد، شتاب جسم چند m/s² است؟', ['5', '10', '2.5', '0'], 2, 'x = ½at² → ½a = 5 → a = 10', 'تست‌های تالیفی', 1403],
        ];

        $questions = [];
        foreach ($qData as [$subject, $type, $difficulty, $text, $options, $correctIdx, $explanation, $source, $year]) {
            $q = Question::create([
                'subject_id' => $subject->id,
                'type' => $type,
                'difficulty' => $difficulty,
                'text' => $text,
                'explanation' => $explanation,
                'source' => $source,
                'year' => $year,
                'default_marks' => 1,
                'is_active' => true,
                'created_by' => $creator->id,
            ]);
            foreach ($options as $i => $opt) {
                $q->options()->create(['order' => $i + 1, 'text' => $opt, 'is_correct' => ($i + 1) === $correctIdx]);
            }
            $questions[] = $q;
        }

        // سؤال تشریحی نمونه
        $descQ = Question::create([
            'subject_id' => $mathCh2->id,
            'type' => 'descriptive',
            'difficulty' => 'medium',
            'text' => 'مفهوم تابع را با یک مثال توضیح دهید و نشان دهید چرا رابطه y² = x تابع نیست.',
            'answer_text' => 'تابع رابطه‌ای است که هر ورودی فقط یک خروجی دارد. در y² = x برای x = 4 دو مقدار y = ±2 ممکن است، پس تابع نیست.',
            'explanation' => 'تست عمودی خط: هر خط عمودی باید نمودار را حداکثر در یک نقطه قطع کند.',
            'created_by' => $creator->id,
        ]);

        // ------------------------------------------------------------------
        // آزمون‌ها
        // ------------------------------------------------------------------
        $exam1 = Exam::create([
            'title' => 'آزمون آزمایشی مبحث توابع',
            'description' => 'آزمون زمان‌دار با نمره منفی برای سنجش سطح شما در مبحث توابع',
            'type' => 'timed',
            'subject_id' => $mathCh2->id,
            'duration_minutes' => 20,
            'negative_marking' => true,
            'negative_value' => 0.25,
            'shuffle_questions' => true,
            'is_public' => true,
            'status' => 'published',
            'created_by' => $teacher->id,
        ]);
        $order = 1;
        foreach (array_slice($questions, 0, 6) as $q) {
            $exam1->examQuestions()->create(['question_id' => $q->id, 'order' => $order++, 'marks' => 1]);
        }
        $exam1->forceFill(['total_marks' => 6])->save();

        $exam2 = Exam::create([
            'title' => 'شبیه‌ساز آزمون جامع (بدون نمره منفی)',
            'description' => 'تمرین آزاد برای مرور مفاهیم فیزیک و شیمی',
            'type' => 'simulator',
            'duration_minutes' => null,
            'is_public' => true,
            'status' => 'published',
            'created_by' => $teacher->id,
        ]);
        $order = 1;
        foreach (array_slice($questions, 6) as $q) {
            $exam2->examQuestions()->create(['question_id' => $q->id, 'order' => $order++, 'marks' => 1]);
        }
        $exam2->forceFill(['total_marks' => count(array_slice($questions, 6))])->save();

        // ------------------------------------------------------------------
        // خلاصه‌های عمومی
        // ------------------------------------------------------------------
        Summary::create(['user_id' => null, 'subject_id' => $mathCh2->id, 'title' => 'نکته طلایی: تشخیص تابع', 'content' => "برای تشخیص تابع بودن یک رابطه، از تست خط عمودی استفاده می‌کنیم: اگر هر خط عمودی نمودار را فقط در یک نقطه قطع کند، رابطه تابع است.\n\nمثال: دایره تابع نیست اما سهمی تابع است.", 'category' => 'نکته', 'is_public' => true, 'created_by' => $creator->id]);
        Summary::create(['user_id' => null, 'subject_id' => $physCh1->id, 'title' => 'خلاصه حرکت‌شناسی: روابط کلیدی', 'content' => "x = vt (حرکت یکنواخت)\nx = ½at² (حرکت شتاب‌دار با v₀=0)\nv² = v₀² + 2ax\n\nنکته: شیب نمودار مکان-زمان = سرعت لحظه‌ای است.", 'category' => 'خلاصه', 'is_public' => true, 'created_by' => $teacher->id]);
        Summary::create(['user_id' => null, 'subject_id' => $chemCh1->id, 'title' => 'فرمول‌های مهم ساختار اتم', 'content' => "نوترون = عدد جرمی − عدد اتمی\nحداکثر الکترون هر تراز: 2n²\nآرایش نوبل: پوسته آخر کامل (ns² np⁶)", 'category' => 'فرمول', 'is_public' => true, 'created_by' => $creator->id]);
        Summary::create(['user_id' => null, 'subject_id' => $bioCh1->id, 'title' => 'نکته: پیوند هیدروژنی', 'content' => "پیوند هیدروژنی ضعیف‌ترین اما فراوان‌ترین پیوند در مولکول‌های زیستی است که ساختار سه‌بعدی پروتئین و DNA را تثبیت می‌کند.", 'category' => 'نکته', 'is_public' => true, 'created_by' => $creator->id]);

        // ------------------------------------------------------------------
        // برنامه مطالعاتی + جلسات مطالعه + هدف
        // ------------------------------------------------------------------
        $plan = StudyPlan::create([
            'user_id' => $student->id,
            'title' => 'برنامه هفتگی آمادگی کنکور',
            'description' => 'برنامه پیشنهادی مشاور برای دو هفته آینده',
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(13)->toDateString(),
            'generated_by' => 'manual',
            'weekly_target_minutes' => 900,
            'created_by' => $counselor->id,
        ]);
        $items = [
            [today(), $mathCh1, 'حل تست ماتریس', 120, 90, 'partial'],
            [today(), $physCh1, 'خلاصه‌نویسی حرکت‌شناسی', 90, 90, 'done'],
            [today()->addDay(), $chemCh1, 'مرور آرایش الکترونی', 90, 0, 'pending'],
            [today()->addDay(), $bioCh1, 'تست مولکول‌های زیستی', 60, 0, 'pending'],
            [today()->addDays(2), $mathCh2, 'مفهوم تابع + تست', 120, 0, 'pending'],
        ];
        foreach ($items as [$date, $subject, $topic, $target, $done, $status]) {
            PlanItem::create(['plan_id' => $plan->id, 'user_id' => $student->id, 'date' => $date, 'subject_id' => $subject->id, 'topic' => $topic, 'target_minutes' => $target, 'completed_minutes' => $done, 'status' => $status]);
        }
        for ($d = 1; $d <= 6; $d++) {
            StudySession::create(['user_id' => $student->id, 'subject_id' => $mathCh1->id, 'date' => today()->subDays($d), 'minutes' => rand(45, 140)]);
        }
        StudySession::create(['user_id' => $student->id, 'subject_id' => $physCh1->id, 'date' => today(), 'minutes' => 70]);
        Goal::create(['user_id' => $student->id, 'period' => 'monthly', 'target_minutes' => 3600, 'target_questions' => 600, 'start_date' => today()->startOfMonth(), 'end_date' => today()->endOfMonth()]);

        // ------------------------------------------------------------------
        // کارنامه آزمایشی دانشجو + مرور خودکار
        // ------------------------------------------------------------------
        $attempt = $student->attempts()->create([
            'exam_id' => $exam1->id,
            'attempt_number' => 1,
            'status' => 'in_progress',
            'started_at' => now()->subDays(2),
        ]);
        foreach ($exam1->examQuestions()->with('question')->get() as $i => $eq) {
            $opts = $eq->question->options;
            $correct = $opts->firstWhere('is_correct');
            // پاسخ‌های متنوع: ۴ صحیح، ۱ غلط، ۱ نزده
            $sel = match ($i) {
                0, 1, 2, 3 => $correct,
                4 => $opts->firstWhere('is_correct', false),
                default => null,
            };
            $attempt->answers()->create([
                'question_id' => $eq->question_id,
                'selected_option_id' => $sel?->id,
                'answered_at' => now()->subDays(2),
                'marks' => $eq->marks,
            ]);
        }
        app(\App\Services\ExamService::class)->grade($attempt);

        // ------------------------------------------------------------------
        // آکادمی: دسته‌ها، دوره‌ها، درس‌ها، ثبت‌نام، کلاس، تکلیف
        // ------------------------------------------------------------------
        $cat = \App\Models\Category::create(['title' => 'کنکور تجربی', 'slug' => 'konkur-tajrobi', 'type' => 'course']);
        $catA = \App\Models\Category::create(['title' => 'مهارت مطالعه', 'slug' => 'study-skills', 'type' => 'article']);

        $course1 = Course::create([
            'title' => 'مدیریت زمان و برنامه‌ریزی هوشمند کنکور',
            'slug' => 'time-management-konkur',
            'description' => 'دوره رایگان مهارت‌های برنامه‌ریزی، پومودورو مطالعاتی و غلبه بر اهمال‌کاری',
            'category_id' => $cat->id,
            'teacher_id' => $counselor->id,
            'price' => 0,
            'is_free' => true,
            'is_published' => true,
            'duration_hours' => 4,
            'created_by' => $admin->id,
        ]);
        foreach ([
            ['چرا برنامه‌ریزی مهم است؟', 'video', 'video/lessons/planning.mp4', 25],
            ['تکنیک پومودورو مطالعاتی', 'text', null, 15],
            ['برنامه هفتگی خود را بسازید', 'text', null, 20],
        ] as $i => [$title, $type, $video, $dur]) {
            Lesson::create(['course_id' => $course1->id, 'title' => $title, 'type' => $type, 'video_path' => $video, 'duration_minutes' => $dur, 'order' => $i + 1, 'is_free_preview' => $i === 0]);
        }

        $course2 = Course::create([
            'title' => 'دوره جامع ریاضیات کنکور (تجربی)',
            'slug' => 'math-full-konkur',
            'description' => 'پوشش کامل مباحث ریاضی کنکور با حل تست‌های سال‌های اخیر',
            'category_id' => $cat->id,
            'teacher_id' => $teacher->id,
            'price' => 1200000,
            'is_free' => false,
            'is_published' => true,
            'duration_hours' => 40,
            'created_by' => $admin->id,
        ]);
        $lessons2 = [
            ['ماتریس: از مقدماتی تا تست', 45],
            ['توابع: مباحث کامل', 50],
            ['حل تست‌های ترکیبی', 60],
        ];
        foreach ($lessons2 as $i => [$title, $dur]) {
            Lesson::create(['course_id' => $course2->id, 'title' => $title, 'type' => 'video', 'video_path' => 'video/lessons/math.mp4', 'duration_minutes' => $dur, 'order' => $i + 1, 'is_free_preview' => $i === 0]);
        }

        // ثبت‌نام دانشجو در دوره رایگان (فعال‌سازی خودکار)
        app(\App\Services\CourseEnrollmentService::class)->enroll($student, $course1, 'free');

        $class = \App\Models\OnlineClass::create([
            'title' => 'کلاس آنلاین حل تست ریاضی — هفته اول',
            'platform' => 'skyroom',
            'join_url' => 'https://demo.skyroom.ir/room/key-math-1',
            'host_url' => 'https://demo.skyroom.ir/room/key-math-1-host',
            'starts_at' => now()->addDays(2)->setTime(18, 0),
            'duration_minutes' => 90,
            'course_id' => $course2->id,
            'teacher_id' => $teacher->id,
            'status' => 'scheduled',
            'created_by' => $teacher->id,
        ]);

        $assignment = Assignment::create([
            'course_id' => $course1->id,
            'title' => 'تکلیف اول: برنامه هفتگی خود را طراحی کنید',
            'description' => "یک برنامه مطالعاتی هفتگی متناسب با هدف خود طراحی کنید و شامل موارد زیر باشد:\n۱) زمان مطالعه هر درس\n۲) زمان مرور\n۳) زمان استراحت",
            'due_at' => now()->addDays(5),
            'max_score' => 100,
            'created_by' => $counselor->id,
        ]);
        Assignment::create([
            'course_id' => $course2->id,
            'title' => 'تکلیف دوم: حل ۲۰ تست توابع',
            'description' => '۲۰ تست از مبحث توابع را انتخاب و حل کنید؛ پاسخ‌ها را با توضیح بنویسید.',
            'due_at' => now()->addDays(7),
            'max_score' => 50,
            'created_by' => $teacher->id,
        ]);

        // ------------------------------------------------------------------
        // مقالات
        // ------------------------------------------------------------------
        $articles = [
            ['چگونه در ۹۰ روز نهایی کنکور را مدیریت کنیم؟', 'phase-90-days', 'برنامه‌ریزی ۹۰ روز پایانی کنکور', "۹۰ روز پایانی کنکور حساس‌ترین بازه آماده‌سازی است. در این مقاله تقسیم‌بندی سه فاز:\n\nفاز اول (روز ۱ تا ۳۰): مرور سریع کل مباحث با اولویت منابع پرتکرار\nفاز دوم (روز ۳۱ تا ۶۰): حل تست موضوعی فشرده\nفاز سوم (روز ۶۱ تا ۹۰): شبیه‌سازهای جامع و مرور اشتباهات\n\nنکته کلیدی: هر تست اشتباه باید در سامانه مرور ثبت شود تا دوباره تکرار نشود."],
            ['تکنیک‌های مرور فاصله‌دار برای ماندگاری مطالب', 'spaced-repetition-guide', 'راهنمای کامل مرور فاصله‌دار', "تحقیقات علوم شناختی نشان می‌دهد مرور فاصله‌دار، ماندگاری حافظه را تا ۲ برابر افزایش می‌دهد.\n\nالگوریتم SM-2 که در سامانه کلید استفاده می‌شود، فاصله مرور را بر اساس کیفیت پاسخ شما تنظیم می‌کند:\n- پاسخ «آسان»: فاصله مرور طولانی‌تر\n- پاسخ «دوباره»: بازگشت فردا\n\nمهم‌ترین اصل: مرور را قبل از فراموشی کامل انجام دهید."],
            ['چطور از کارنامه تحلیلی برای بهبود عملکرد استفاده کنیم؟', 'carnival-analysis', 'تحلیل کارنامه حرفه‌ای', "کارنامه فقط عدد نیست؛ نقشه راه شماست.\n\n۱) درصد پایین + تعداد کم سؤال = مبحث خوانده‌نشده\n۲) درصد متوسط + تعداد بالا = مبحث خوانده ولی نیازمند تست\n۳) درصدهای نوسانی = تسلط ناپایدار؛ نیازمند مرور\n\nدر صفحه تحلیل عملکرد سامانه کلید، سطح تسلط هر مبحث به‌صورت خودکار محاسبه می‌شود."],
        ];
        foreach ($articles as [$title, $slug, $excerpt, $content]) {
            \App\Models\Article::create([
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $excerpt,
                'content' => $content,
                'category_id' => $catA->id,
                'author_id' => $creator->id,
                'status' => 'published',
                'is_public' => true,
                'published_at' => now()->subDays(rand(1, 20)),
                'views' => rand(50, 900),
                'meta_title' => $title,
                'meta_description' => $excerpt,
            ]);
        }

        // ------------------------------------------------------------------
        // اشتراک‌ها و فروش
        // ------------------------------------------------------------------
        $planData = [
            ['اشتراک آزمایشی', 'trial', 0, 7, ['دسترسی کامل ۷ روزه', 'همه ماژول‌ها'], 'رایگان'],
            ['اشتراک ماهانه', 'monthly', 290000, 30, ['آزمون نامحدود', 'مرور هوشمند', 'برنامه مطالعاتی'], null],
            ['اشتراک سه‌ماهه', 'quarterly', 790000, 90, ['همه امکانات ماهانه', '۲ جلسه مشاوره هدیه'], 'محبوب'],
            ['اشتراک سالانه', 'yearly', 2490000, 365, ['همه امکانات', 'مشاوره ماهانه', 'گواهینامه دوره‌ها'], 'بهترین ارزش'],
            ['بسته حرفه‌ای کنکور', 'pro', 3900000, 365, ['اشتراک سالانه', 'تمام دوره‌های آکادمی', 'پشتیبانی اولویت‌دار'], null],
        ];
        $plans = [];
        foreach ($planData as $i => [$title, $slug, $price, $days, $features, $badge]) {
            $plans[$slug] = SubscriptionPlan::create(['title' => $title, 'slug' => $slug, 'price' => $price, 'duration_days' => $days, 'features' => $features, 'badge' => $badge, 'sort' => $i]);
        }

        // اشتراک فعال دانشجو (هدیه ادمین)
        app(\App\Services\CommerceService::class)->grantSubscription($student, $plans['quarterly'], 'grant');

        // کیف پول نمونه
        WalletTransaction::create(['user_id' => $student->id, 'amount' => 200000, 'balance_after' => 200000, 'type' => 'deposit', 'description' => 'شارژ اولیه کیف پول (نمونه)']);

        // کوپن نمونه
        \App\Models\Coupon::create(['code' => 'KEY-10', 'type' => 'percent', 'value' => 10, 'max_uses' => 100, 'is_active' => true]);

        // محصول آموزشی
        \App\Models\Product::create(['title' => 'دفتر برنامه‌ریزی چاپی کلید', 'slug' => 'key-planner', 'description' => 'دفتر برنامه‌ریزی روزانه مخصوص داوطلبان کنکور', 'price' => 180000]);
        \App\Models\Product::create(['title' => 'کارت‌های مرور جیبی', 'slug' => 'flash-cards', 'description' => 'مجموعه کارت‌های مرور فاصله‌دار قابل حمل', 'price' => 120000]);

        // ------------------------------------------------------------------
        // دانشگاه‌ها، رشته‌ها و حدنصاب
        // ------------------------------------------------------------------
        $uniData = [
            ['دانشگاه تهران', 'تهران', 'state'],
            ['دانشگاه صنعتی شریف', 'تهران', 'state'],
            ['دانشگاه علوم پزشکی تهران', 'تهران', 'state'],
            ['دانشگاه اصفهان', 'اصفهان', 'state'],
            ['دانشگاه آزاد تهران جنوب', 'تهران', 'azad'],
        ];
        $unis = [];
        foreach ($uniData as [$name, $city, $type]) {
            $unis[$name] = University::create(['name' => $name, 'city' => $city, 'type' => $type]);
        }

        $majorData = [
            ['مهندسی کامپیوتر', 'math'],
            ['مهندسی پزشکی', 'experimental'],
            ['پزشکی عمومی', 'experimental'],
            ['فیزیک', 'math'],
            ['روان‌شناسی', 'humanities'],
        ];
        $majors = [];
        foreach ($majorData as [$title, $group]) {
            $majors[$title] = Major::create(['title' => $title, 'group' => $group]);
        }

        $cutoffs = [
            ['دانشگاه تهران', 'مهندسی کامپیوتر', 250, 780, 'daily'],
            ['دانشگاه تهران', 'مهندسی پزشکی', 900, 1600, 'daily'],
            ['دانشگاه صنعتی شریف', 'مهندسی کامپیوتر', 120, 450, 'daily'],
            ['دانشگاه علوم پزشکی تهران', 'پزشکی عمومی', 180, 480, 'daily'],
            ['دانشگاه اصفهان', 'مهندسی کامپیوتر', 700, 1500, 'daily'],
            ['دانشگاه اصفهان', 'فیزیک', 1200, 2600, 'daily'],
            ['دانشگاه تهران', 'روان‌شناسی', 700, 1400, 'daily'],
            ['دانشگاه آزاد تهران جنوب', 'مهندسی کامپیوتر', 1800, 4200, 'full'],
        ];
        foreach ($cutoffs as [$uni, $major, $min, $max, $tuition]) {
            MajorUniversity::create([
                'university_id' => $unis[$uni]->id,
                'major_id' => $majors[$major]->id,
                'year' => 1403,
                'last_rank_min' => $min,
                'last_rank_max' => $max,
                'tuition_type' => $tuition,
            ]);
        }

        // نتیجه تاریخی دانشجو (برای کالیبراسیون تخمین رتبه)
        HistoricalResult::create([
            'user_id' => $student->id,
            'year' => 1402,
            'exam_name' => 'آزمون نهایی دوازدهم',
            'total_percent' => 78.5,
            'rank' => 12000,
            'subjects' => [
                ['title' => 'ریاضیات', 'percent' => 82, 'count' => 40],
                ['title' => 'فیزیک', 'percent' => 75, 'count' => 35],
                ['title' => 'شیمی', 'percent' => 78, 'count' => 35],
                ['title' => 'زیست‌شناسی', 'percent' => 80, 'count' => 50],
            ],
            'source' => 'manual',
        ]);

        // ------------------------------------------------------------------
        // گواهینامه نمونه + جلسه مشاوره + تیکت
        // ------------------------------------------------------------------
        \App\Http\Controllers\CertificateController::issue($student, 'گواهینامه اتمام دوره مدیریت زمان', $course1, null, 'fa', $admin);

        \App\Models\CounselingSession::create([
            'counselor_id' => $counselor->id,
            'student_id' => $student->id,
            'scheduled_at' => now()->addDays(1)->setTime(17, 30),
            'duration_minutes' => 45,
            'mode' => 'online',
            'status' => 'scheduled',
        ]);
        \App\Models\CounselingSession::create([
            'counselor_id' => $counselor->id,
            'student_id' => $student->id,
            'scheduled_at' => now()->subDays(10),
            'duration_minutes' => 40,
            'mode' => 'online',
            'summary' => 'بررسی وضعیت مطالعه فصل اول و تنظیم برنامه هفته',
            'recommendations' => 'افزایش تعداد تست روزانه به ۳۰ تست و ثبت مرور روزانه',
            'status' => 'done',
        ]);

        $ticket = \App\Models\Ticket::create(['user_id' => $student->id, 'subject' => 'مشکل در نمایش فرمول‌های ریاضی', 'category' => 'فنی', 'priority' => 'normal', 'status' => 'answered', 'assigned_to' => $expert->id]);
        \App\Models\TicketMessage::create(['ticket_id' => $ticket->id, 'user_id' => $student->id, 'body' => 'سلام، فرمول‌ها در بعضی سؤالات درست نمایش داده نمی‌شود.']);
        \App\Models\TicketMessage::create(['ticket_id' => $ticket->id, 'user_id' => $expert->id, 'body' => 'سلام، وقت بخیر. فرمول‌ها با MathJax نمایش داده می‌شوند؛ اگر مشکل ادامه داشت از مرورگر دیگری امتحان کنید.', 'is_staff' => true]);
        $ticket->forceFill(['last_reply_at' => now()])->save();

        // اتاق گفتگوی خصوصی مشاور و دانشجو
        $room = \App\Models\ChatRoom::privateBetween($counselor, $student);
        \App\Models\ChatMessage::create(['chat_room_id' => $room->id, 'user_id' => $counselor->id, 'body' => 'سلام علی! برنامه امروز را دیدم؛ خیلی خوب پیش رفتی. سؤالی داری؟']);
        \App\Models\ChatMessage::create(['chat_room_id' => $room->id, 'user_id' => $student->id, 'body' => 'سلام، ممنون. فقط در مبحث توابع چند تست داشتم.']);

        // گروه مطالعاتی
        $group = \App\Models\ChatRoom::create(['type' => 'group', 'name' => 'گروه مطالعاتی کنکور تجربی', 'created_by' => $student->id]);
        $group->users()->syncWithoutDetaching([$student->id, $student2->id, $student3->id]);
        \App\Models\ChatMessage::create(['chat_room_id' => $group->id, 'user_id' => $student2->id, 'body' => 'امروز ۸۰ تست زدم! کسی می‌خواهد چالش بگذارد؟']);
    }
}
