<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Category;
use App\Models\ClassAttendance;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\OnlineClass;
use App\Services\CourseEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * تجربه دانشجویی آکادمی: دوره‌ها، ثبت‌نام، درس‌ها، کلاس آنلاین و تکالیف
 */
class AcademyController extends Controller
{
    // ------------------------------------------------------------------
    // دوره‌ها
    // ------------------------------------------------------------------

    /** فهرست دوره‌های منتشرشده با امکان جستجو و فیلتر دسته */
    public function coursesIndex(Request $request)
    {
        $user = $request->user();

        $courses = Course::query()
            ->where('is_published', true)
            ->with(['teacher:id,name', 'category:id,title,slug'])
            ->withCount(['lessons', 'enrollments'])
            ->when($request->filled('q'), fn ($q) => $q->where(
                fn ($w) => $w->where('title', 'like', '%' . $request->q . '%')
                    ->orWhere('description', 'like', '%' . $request->q . '%')
            ))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('type'), function ($q) use ($request) {
                if ($request->type === 'free') {
                    $q->where('is_free', true);
                } elseif ($request->type === 'paid') {
                    $q->where('is_free', false);
                }
            })
            ->latest('id')
            ->paginate(9)
            ->withQueryString();

        $enrolledIds = $user->enrollments()->pluck('course_id')->all();

        $stats = [
            'all' => Course::query()->where('is_published', true)->count(),
            'free' => Course::query()->where('is_published', true)->where('is_free', true)->count(),
            'mine' => count($enrolledIds),
        ];

        $categories = Category::query()->where('type', 'course')->orderBy('order')->get();

        return view('academy.courses', compact('courses', 'enrolledIds', 'stats', 'categories'));
    }

    /** جزئیات دوره + درس‌ها + کنترل دسترسی */
    public function coursesShow(Request $request, Course $course)
    {
        if (! $course->is_published && ! $request->user()->isStaff()) {
            abort(404);
        }

        $user = $request->user();
        $enrolled = $course->isEnrolled($user);
        $enrollment = CourseEnrollment::query()
            ->where('course_id', $course->id)->where('user_id', $user->id)->first();

        $course->load(['teacher:id,name', 'category:id,title']);
        $lessons = $course->lessons()->withCount([])->get();
        $completedIds = LessonProgress::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereNotNull('completed_at')
            ->pluck('lesson_id')
            ->all();

        $certificate = \App\Models\Certificate::query()
            ->where('user_id', $user->id)->where('course_id', $course->id)->first();

        return view('academy.course', compact(
            'course', 'lessons', 'enrolled', 'enrollment', 'completedIds', 'certificate'
        ));
    }

    /** ثبت‌نام: فقط دوره رایگان یا دوره خریداری‌شده (ثبت‌نام نداشته باشد) */
    public function enroll(Request $request, Course $course)
    {
        abort_unless($course->is_published, 404);

        $user = $request->user();

        if ($course->isEnrolled($user)) {
            return redirect()->route('academy.courses.show', $course)
                ->with('info', 'شما قبلاً در این دوره ثبت‌نام کرده‌اید.');
        }

        if (! $course->is_free) {
            return redirect()->route('academy.courses.show', $course)
                ->with('error', 'این دوره پولی است. برای دسترسی ابتدا دوره را از فروشگاه خریداری کنید.');
        }

        CourseEnrollmentService::enroll($user, $course, 'free');

        return redirect()->route('academy.courses.show', $course)
            ->with('success', 'ثبت‌نام شما در دوره «' . $course->title . '» با موفقیت انجام شد.');
    }

    // ------------------------------------------------------------------
    // درس‌ها
    // ------------------------------------------------------------------

    /** نمایش یک درس (ویدیو/متن/PDF) */
    public function lesson(Request $request, Course $course, Lesson $lesson)
    {
        abort_unless($lesson->course_id === $course->id, 404);

        $user = $request->user();
        $enrolled = $course->isEnrolled($user);
        abort_unless($enrolled || $lesson->is_free_preview, 403, 'برای مشاهده این درس باید در دوره ثبت‌نام کنید.');

        $lessons = $course->lessons()->get();
        $index = $lessons->search(fn ($l) => $l->id === $lesson->id);
        $prev = $index > 0 ? $lessons[$index - 1] : null;
        $next = $index < $lessons->count() - 1 ? $lessons[$index + 1] : null;

        $progress = LessonProgress::query()
            ->where('user_id', $user->id)->where('lesson_id', $lesson->id)->first();

        $media = null;
        if ($lesson->type === 'pdf' && $lesson->media_id) {
            $media = DB::table('media')->where('id', $lesson->media_id)->first();
        }

        return view('academy.lesson', compact('course', 'lesson', 'lessons', 'prev', 'next', 'enrolled', 'progress', 'media'));
    }

    /** تکمیل درس + محاسبه پیشرفت + صدور خودکار گواهینامه */
    public function lessonComplete(Request $request, Course $course, Lesson $lesson)
    {
        abort_unless($lesson->course_id === $course->id, 404);

        $user = $request->user();
        abort_unless($course->isEnrolled($user), 403, 'ابتدا در دوره ثبت‌نام کنید.');

        $data = $request->validate([
            'seconds_spent' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ]);

        $progressRow = LessonProgress::query()->firstOrNew(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id]
        );
        $progressRow->course_id = $course->id;
        $progressRow->completed_at = now();
        $progressRow->seconds_spent = min(
            $progressRow->seconds_spent + (int) ($data['seconds_spent'] ?? 0),
            86400
        );
        $progressRow->save();

        $progress = CourseEnrollmentService::recomputeProgress($user, $course);
        \App\Models\AuditLog::record($user, 'lesson.completed', $lesson, ['course' => $course->id, 'progress' => $progress]);

        // صدور خودکار گواهینامه پس از تکمیل ۱۰۰٪ دوره
        if ($progress >= 100) {
            $hasCertificate = \App\Models\Certificate::query()
                ->where('user_id', $user->id)->where('course_id', $course->id)->exists();

            if (! $hasCertificate) {
                $certificate = CertificateController::issue(
                    $user,
                    'گواهینامه تکمیل دوره: ' . $course->title,
                    $course,
                    null,
                    'fa',
                    $user
                );

                return redirect()->route('certificates.show', $certificate)
                    ->with('success', 'تبریک! دوره را ۱۰۰٪ تکمیل کردید و گواهینامه شما صادر شد.');
            }
        }

        return redirect()->route('academy.lesson', [$course, $lesson])
            ->with('success', 'درس با موفقیت تکمیل شد. پیشرفت دوره: ' . $progress . '٪');
    }

    // ------------------------------------------------------------------
    // دوره‌های من
    // ------------------------------------------------------------------

    /** دوره‌های ثبت‌نام‌شده با نوار پیشرفت */
    public function myCourses(Request $request)
    {
        $user = $request->user();

        $enrollments = $user->enrollments()
            ->with(['course.teacher:id,name', 'course.category:id,title'])
            ->orderByDesc('id')
            ->paginate(12);

        $coursesById = Course::query()
            ->whereIn('id', $enrollments->getCollection()->pluck('course_id'))
            ->withCount(['lessons'])
            ->get()
            ->keyBy('id');

        $doneCounts = LessonProgress::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->whereIn('course_id', $enrollments->getCollection()->pluck('course_id'))
            ->selectRaw('course_id, count(*) as done')
            ->groupBy('course_id')
            ->pluck('done', 'course_id');

        return view('academy.my-courses', compact('enrollments', 'coursesById', 'doneCounts'));
    }

    // ------------------------------------------------------------------
    // کلاس‌های آنلاین
    // ------------------------------------------------------------------

    /** کلاس‌های دوره‌های ثبت‌نام‌شده + کلاس‌های عمومی */
    public function classes(Request $request)
    {
        $user = $request->user();

        $enrolledCourseIds = $user->enrollments()
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->pluck('course_id');

        $classes = OnlineClass::query()
            ->with(['course:id,title,slug', 'teacher:id,name'])
            ->withCount('attendances')
            ->where(fn ($q) => $q->whereNull('course_id')->orWhereIn('course_id', $enrolledCourseIds))
            ->orderByDesc('starts_at')
            ->get();

        $myAttendance = ClassAttendance::query()
            ->where('user_id', $user->id)
            ->whereIn('class_id', $classes->pluck('id'))
            ->get()
            ->keyBy('class_id');

        return view('academy.classes', compact('classes', 'myAttendance'));
    }

    /** ثبت حضور و ورود به کلاس */
    public function joinClass(Request $request, OnlineClass $class)
    {
        $user = $request->user();

        if ($class->course_id && ! $class->course->isEnrolled($user)) {
            abort(403, 'این کلاس مخصوص دانشجویان دوره است.');
        }

        ClassAttendance::query()->updateOrCreate(
            ['class_id' => $class->id, 'user_id' => $user->id],
            ['joined_at' => now(), 'status' => 'joined']
        );

        \App\Models\AuditLog::record($user, 'class.joined', $class, ['class' => $class->id]);

        if ($class->join_url) {
            return redirect()->away($class->join_url);
        }

        return redirect()->route('academy.classes')
            ->with('success', 'حضور شما در کلاس «' . $class->title . '» ثبت شد.');
    }

    // ------------------------------------------------------------------
    // تکالیف
    // ------------------------------------------------------------------

    /** تکالیف دوره‌های ثبت‌نام‌شده */
    public function myAssignments(Request $request)
    {
        $user = $request->user();

        $courseIds = $user->enrollments()->pluck('course_id');

        $assignments = Assignment::query()
            ->whereIn('course_id', $courseIds)
            ->with(['course:id,title,slug'])
            ->with(['submissions' => fn ($q) => $q->where('user_id', $user->id)])
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('academy.my-assignments', compact('assignments'));
    }

    /** نمایش تکلیف + وضعیت ارسال من */
    public function assignmentShow(Request $request, Assignment $assignment)
    {
        $user = $request->user();

        $enrolled = $assignment->course_id
            ? $assignment->course->isEnrolled($user)
            : true;

        abort_unless($enrolled || $user->isStaff(), 403, 'این تکلیف مخصوص دانشجویان دوره است.');

        $assignment->load('course:id,title,slug');
        $submission = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first();

        return view('academy.assignment', compact('assignment', 'submission'));
    }

    /** ارسال تکلیف: متن + فایل در storage/app/public/assignments */
    public function assignmentSubmit(Request $request, Assignment $assignment)
    {
        $user = $request->user();

        $enrolled = $assignment->course_id
            ? $assignment->course->isEnrolled($user)
            : true;

        abort_unless($enrolled, 403, 'ابتدا در دوره ثبت‌نام کنید.');

        $submission = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first();

        if ($submission && $submission->status === 'graded') {
            return redirect()->route('academy.assignment.show', $assignment)
                ->with('error', 'این تکلیف تصحیح شده و امکان ویرایش مجدد وجود ندارد.');
        }

        $data = $request->validate([
            'text_answer' => ['nullable', 'string', 'max:10000'],
            'file' => [
                'nullable', 'file', 'max:20480',
                'mimes:pdf,doc,docx,zip,rar,png,jpg,jpeg,txt,mp3,mp4',
            ],
        ], [
            'file.mimes' => 'فرمت فایل مجاز نیست (pdf، word، zip، تصویر، متن، صوت یا ویدیو).',
            'file.max' => 'حجم فایل حداکثر ۲۰ مگابایت است.',
        ]);

        $filePath = $submission->file_path;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('assignments', 'public');
        }

        if (! $filePath && empty(trim((string) ($data['text_answer'] ?? '')))) {
            return back()->with('error', 'متن پاسخ یا فایل تکلیف را وارد کنید.')->withInput();
        }

        AssignmentSubmission::query()->updateOrCreate(
            ['assignment_id' => $assignment->id, 'user_id' => $user->id],
            [
                'text_answer' => $data['text_answer'] ?? null,
                'file_path' => $filePath,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]
        );

        \App\Models\AuditLog::record($user, 'assignment.submitted', $assignment, ['file' => (bool) $request->hasFile('file')]);

        return redirect()->route('academy.assignment.show', $assignment)
            ->with('success', 'تکلیف شما با موفقیت ارسال شد.');
    }
}
