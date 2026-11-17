<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CounselingSession;
use App\Models\FollowUp;
use App\Models\OnlineClass;
use App\Models\PlanItem;
use App\Models\StudyPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\CounselingScheduledNotification;
use App\Services\MasteryService;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * پنل مشاور: دانشجویان تخصیص‌یافته، پرونده جامع دانشجو (Student 360)،
 * جلسات مشاوره و پیگیری‌ها
 */
class CounselorController extends Controller
{
    public function __construct(
        private ReviewService $reviews,
        private MasteryService $mastery,
    ) {}

    // ------------------------------------------------------------------
    // دسترسی
    // ------------------------------------------------------------------

    /** مدیر یا مشاورِ تخصیص‌یافته به این دانشجو */
    private function authorizeStudent(User $student): void
    {
        $user = auth()->user();

        if ($user->canAccess('users.manage')) {
            return; // مدیر: دسترسی کامل
        }

        if (! $user->canAccess('counselor.panel')) {
            abort(403, 'شما به پرونده این دانشجو دسترسی ندارید.');
        }

        $assigned = $student->counselors()->where('users.id', $user->id)->exists();
        if (! $assigned) {
            abort(403, 'این دانشجو به شما تخصیص نیافته است.');
        }
    }

    // ------------------------------------------------------------------
    // دانشجویان من
    // ------------------------------------------------------------------

    public function students(Request $request)
    {
        $user = auth()->user();
        $reviewService = $this->reviews;

        $students = $user->students()
            ->withCount(['attempts as attempts_finished_count' => fn ($q) => $q->where('status', 'finished')])
            ->orderBy('name')
            ->get();

        // خلاصه وضعیت هر دانشجو: آخرین آزمون، مطالعه ۷ روز، مرور در انتظار، اشتراک
        $summaries = [];
        foreach ($students as $student) {
            $lastAttempt = $student->attempts()->where('status', 'finished')
                ->with('exam')->latest('finished_at')->first();

            $summaries[$student->id] = [
                'lastAttempt' => $lastAttempt,
                'study7' => (int) $student->studySessions()->where('date', '>=', now()->subDays(7)->toDateString())->sum('minutes'),
                'pendingReviews' => $reviewService->pendingCount($student),
                'activeSubscription' => Subscription::query()
                    ->where('user_id', $student->id)
                    ->where('status', 'active')->where('ends_at', '>', now())
                    ->latest('ends_at')->first(),
            ];
        }

        $counselors = collect();
        $allStudents = collect();
        if ($user->canAccess('users.manage')) {
            $counselors = User::query()->whereHas('roles', fn ($q) => $q->where('slug', 'counselor'))->orderBy('name')->get();
            $allStudents = User::query()->students()->active()->orderBy('name')->get();
        }

        return view('counselor.students', compact('students', 'summaries', 'counselors', 'allStudents'));
    }

    // ------------------------------------------------------------------
    // Student 360
    // ------------------------------------------------------------------

    public function show(User $student)
    {
        $this->authorizeStudent($student);

        return $this->render360($student);
    }

    /** مسیر مستقیم مدیریت: students/{user}/360 */
    public function direct(User $user)
    {
        $this->authorizeStudent($user);

        return $this->render360($user);
    }

    private function render360(User $student)
    {
        $viewer = auth()->user();

        // اطلاعات فردی/آموزشی
        $profile = [
            'grade' => $student->grade,
            'field' => $student->field_group,
            'city' => $student->city,
            'phone' => $student->phone,
            'email' => $student->email,
            'joined' => $student->created_at,
            'lastLogin' => $student->last_login_at,
            'lastSeen' => $student->last_seen_at,
        ];

        // برنامه مطالعاتی فعال و آیتم‌های آن
        $activePlan = StudyPlan::query()
            ->where('user_id', $student->id)
            ->where('status', 'active')
            ->orderByDesc('id')->first();
        $planItems = $activePlan
            ? PlanItem::query()->where('plan_id', $activePlan->id)
                ->orderBy('date')->orderBy('id')->take(14)->get()
            : collect();

        // عملکرد واقعی
        $attempts = $student->attempts()->where('status', 'finished')
            ->with('exam')->orderByDesc('finished_at')->take(12)->get();

        $performance = [
            'attemptsCount' => $student->attempts()->where('status', 'finished')->count(),
            'avgPercent' => round((float) ($student->attempts()->where('status', 'finished')->avg('percent') ?? 0), 1),
            'questionsAnswered' => (int) DB::table('attempt_answers as aa')
                ->join('exam_attempts as at', 'at.id', '=', 'aa.attempt_id')
                ->where('at.user_id', $student->id)->where('at.status', 'finished')->count(),
            'study7' => (int) $student->studySessions()->where('date', '>=', now()->subDays(7)->toDateString())->sum('minutes'),
            'study30' => (int) $student->studySessions()->where('date', '>=', now()->subDays(30)->toDateString())->sum('minutes'),
            'consistency' => $this->consistencyPercent($student, 14),
        ];

        // نمودارها
        $trend = $attempts->sortBy('finished_at')->values()
            ->map(fn ($a) => ['label' => fa_date($a->finished_at), 'value' => (float) $a->percent]);
        $masteryRows = $student->mastery()->where('attempted', '>', 0)
            ->with('subject')->orderByDesc('level')->get();

        // مرورها و ماندگاری
        $retention = $this->reviews->retentionStats($student);
        $reviewStats = [
            'pending' => $this->reviews->pendingCount($student),
            'total' => $student->reviews()->count(),
            'retention' => $retention['retention'],
            'retentionCount' => $retention['total'],
        ];

        // اهداف
        $goals = DB::table('goals')->where('user_id', $student->id)->orderByDesc('id')->take(5)->get();

        // خریدها و اشتراک
        $orders = $student->orders()->with('items')->orderByDesc('id')->take(8)->get();
        $subscription = Subscription::query()
            ->where('user_id', $student->id)
            ->where('status', 'active')->where('ends_at', '>', now())
            ->with('plan')->latest('ends_at')->first();

        // دوره‌ها و کلاس‌ها
        $enrollments = $student->enrollments()->with('course')->orderByDesc('id')->get();
        $courseIds = $enrollments->pluck('course_id')->filter()->all();
        $classes = OnlineClass::query()
            ->whereIn('course_id', $courseIds)
            ->with('course')->orderByDesc('starts_at')->take(8)->get();

        // تخمین رتبه و نتیجه تاریخی
        $rankEstimate = $student->rankEstimates()->orderByDesc('id')->first();
        $historical = $student->historicalResults()->orderByDesc('year')->get();

        // تیکت‌ها
        $tickets = $student->tickets()->orderByDesc('id')->take(8)->get();

        // جلسات مشاوره و پیگیری‌ها
        $sessions = CounselingSession::query()
            ->where('student_id', $student->id)
            ->when(! $viewer->canAccess('users.manage'), fn ($q) => $q->where('counselor_id', $viewer->id))
            ->with(['counselor'])->orderByDesc('scheduled_at')->get();
        $followups = FollowUp::query()
            ->where('student_id', $student->id)
            ->when(! $viewer->canAccess('users.manage'), fn ($q) => $q->where('counselor_id', $viewer->id))
            ->orderByDesc('id')->get();

        return view('counselor.student360', compact(
            'student', 'profile', 'activePlan', 'planItems', 'performance', 'attempts',
            'trend', 'masteryRows', 'reviewStats', 'goals', 'orders', 'subscription',
            'enrollments', 'classes', 'tickets', 'sessions', 'followups',
            'rankEstimate', 'historical'
        ));
    }

    private function consistencyPercent(User $student, int $days): float
    {
        $studied = $student->studySessions()
            ->where('date', '>=', now()->subDays($days - 1)->toDateString())
            ->distinct('date')->count('date');

        return percent($studied, $days);
    }

    // ------------------------------------------------------------------
    // جلسات مشاوره
    // ------------------------------------------------------------------

    public function sessions()
    {
        $user = auth()->user();

        $sessions = CounselingSession::query()
            ->where('counselor_id', $user->id)
            ->with('student')->orderByDesc('scheduled_at')->get();

        $students = $user->students()->orderBy('name')->get();
        $upcoming = $sessions->where('status', 'scheduled')
            ->sortBy('scheduled_at')->values();

        return view('counselor.sessions', compact('sessions', 'students', 'upcoming'));
    }

    public function sessionsStore(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'mode' => ['required', 'in:online,phone,in_person'],
            'note' => ['nullable', 'string', 'max:2000'],
        ], [
            'student_id.required' => 'انتخاب دانشجو الزامی است',
            'scheduled_at.required' => 'زمان جلسه الزامی است',
            'scheduled_at.after' => 'زمان جلسه باید در آینده باشد',
            'mode.required' => 'نوع جلسه الزامی است',
        ]);

        $student = User::findOrFail($data['student_id']);
        if (! $user->canAccess('users.manage')) {
            if (! $user->students()->where('users.id', $student->id)->exists()) {
                abort(403, 'این دانشجو به شما تخصیص نیافته است.');
            }
        }

        $session = CounselingSession::create([
            'counselor_id' => $user->id,
            'student_id' => $student->id,
            'scheduled_at' => Jalali::toLatinDigits($data['scheduled_at']),
            'duration_minutes' => $data['duration_minutes'] ?? 45,
            'mode' => $data['mode'],
            'summary' => $data['note'] ?? null,
            'status' => 'scheduled',
        ]);

        $student->notify(new CounselingScheduledNotification($session, 'scheduled'));
        AuditLog::record($user, 'counseling.session.store', $session, ['student_id' => $student->id, 'scheduled_at' => $session->scheduled_at->toDateTimeString()]);

        return back()->with('success', 'جلسه مشاوره ثبت شد و اعلان به دانشجو ارسال گردید.');
    }

    public function sessionsUpdate(Request $request, CounselingSession $session)
    {
        $user = auth()->user();

        if ($session->counselor_id !== $user->id && ! $user->canAccess('users.manage')) {
            abort(403, 'این جلسه متعلق به شما نیست.');
        }

        $data = $request->validate([
            'summary' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:scheduled,done,canceled'],
        ], [
            'status.required' => 'وضعیت جلسه الزامی است',
        ]);

        $oldStatus = $session->status;
        $session->update($data);

        if ($data['status'] !== $oldStatus && in_array($data['status'], ['done', 'canceled'])) {
            $session->student->notify(new CounselingScheduledNotification($session, $data['status']));
        } elseif ($data['status'] === 'scheduled') {
            $session->student->notify(new CounselingScheduledNotification($session, 'updated'));
        }

        AuditLog::record($user, 'counseling.session.update', $session, ['status' => $data['status']]);

        return back()->with('success', 'جلسه مشاوره به‌روزرسانی شد.');
    }

    // ------------------------------------------------------------------
    // پیگیری‌ها
    // ------------------------------------------------------------------

    public function followups()
    {
        $user = auth()->user();

        $followups = FollowUp::query()
            ->where('counselor_id', $user->id)
            ->with('student')->orderByDesc('id')->get();

        $students = $user->students()->orderBy('name')->get();

        return view('counselor.followups', compact('followups', 'students'));
    }

    public function followupsStore(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'note' => ['required', 'string', 'max:3000'],
            'next_date' => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'student_id.required' => 'انتخاب دانشجو الزامی است',
            'note.required' => 'متن پیگیری الزامی است',
            'next_date.after_or_equal' => 'تاریخ پیگیری بعدی نمی‌تواند در گذشته باشد',
        ]);

        $student = User::findOrFail($data['student_id']);
        if (! $user->canAccess('users.manage') && ! $user->students()->where('users.id', $student->id)->exists()) {
            abort(403, 'این دانشجو به شما تخصیص نیافته است.');
        }

        $followup = FollowUp::create([
            'counselor_id' => $user->id,
            'student_id' => $student->id,
            'note' => $data['note'],
            'next_date' => $data['next_date'] ?? null,
            'status' => 'open',
        ]);

        AuditLog::record($user, 'counselor.followup.store', $followup, ['student_id' => $student->id]);

        return back()->with('success', 'پیگیری ثبت شد.');
    }

    public function followupsDone(Request $request, FollowUp $followup)
    {
        $user = auth()->user();

        if ($followup->counselor_id !== $user->id && ! $user->canAccess('users.manage')) {
            abort(403, 'این پیگیری متعلق به شما نیست.');
        }

        $followup->update(['status' => 'done']);
        AuditLog::record($user, 'counselor.followup.done', $followup);

        return back()->with('success', 'پیگیری بسته شد.');
    }

    // ------------------------------------------------------------------
    // تخصیص / آرشیو دانشجو
    // ------------------------------------------------------------------

    public function assign(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'counselor_id' => ['required', 'integer', 'exists:users,id'],
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'counselor_id.required' => 'انتخاب مشاور الزامی است',
            'student_id.required' => 'انتخاب دانشجو الزامی است',
        ]);

        $student = User::findOrFail($data['student_id']);
        if (! $student->hasRole('student')) {
            return back()->withErrors(['student_id' => 'کاربر انتخاب‌شده دانشجو نیست.']);
        }

        $pivot = \App\Models\CounselorStudent::updateOrCreate(
            ['counselor_id' => $data['counselor_id'], 'student_id' => $student->id],
            ['status' => 'active', 'note' => $data['note'] ?? null, 'assigned_by' => $user->id]
        );

        AuditLog::record($user, 'counselor.students.assign', $pivot, [
            'counselor_id' => $data['counselor_id'], 'student_id' => $student->id,
        ]);

        return back()->with('success', 'دانشجو به مشاور تخصیص داده شد.');
    }

    public function archive(Request $request, User $student)
    {
        $user = auth()->user();

        $pivot = \App\Models\CounselorStudent::query()
            ->when($user->canAccess('users.manage'),
                fn ($q) => $q->where('student_id', $student->id),
                fn ($q) => $q->where('student_id', $student->id)->where('counselor_id', $user->id))
            ->first();

        if (! $pivot) {
            abort(404, 'تخصیصی برای این دانشجو یافت نشد.');
        }

        $newStatus = $pivot->status === 'archived' ? 'active' : 'archived';
        $pivot->update(['status' => $newStatus]);

        AuditLog::record($user, 'counselor.students.' . ($newStatus === 'archived' ? 'archive' : 'restore'), $pivot, ['student_id' => $student->id]);

        return back()->with('success', $newStatus === 'archived'
            ? 'دانشجو آرشیو شد (از فهرست دانشجویان فعال شما خارج شد).'
            : 'دانشجو از آرشیو خارج و فعال شد.');
    }
}
