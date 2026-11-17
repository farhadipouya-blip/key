<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\OnlineClass;
use App\Models\Subscription;
use App\Models\StudyPlan;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Support\Facades\DB;

/**
 * داشبوردهای نقش‌محور: مدیر، دانشجو، مشاور، مدرس
 */
class DashboardController extends Controller
{
    /** مسیر عمومی داشبورد — بر اساس نقش هدایت می‌شود */
    public function index()
    {
        $user = auth()->user();
        return redirect($user->dashboardRoute());
    }

    public function admin()
    {
        $user = auth()->user();

        $stats = [
            'users' => User::count(),
            'students' => User::whereHas('roles', fn ($q) => $q->where('slug', 'student'))->count(),
            'newUsers' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'online' => User::where('last_seen_at', '>=', now()->subMinutes(5))->count(),
            'counselors' => User::whereHas('roles', fn ($q) => $q->where('slug', 'counselor'))->count(),
            'teachers' => User::whereHas('roles', fn ($q) => $q->where('slug', 'teacher'))->count(),
            'exams' => DB::table('exams')->count(),
            'questions' => DB::table('questions')->count(),
            'studyMinutes' => (int) (DB::table('study_sessions')->where('date', '>=', now()->subDays(7))->sum('minutes')),
            'revenue' => (float) (DB::table('transactions')->where('status', 'success')->sum('amount')),
            'activeSubs' => Subscription::query()->where('status', 'active')->where('ends_at', '>', now())->count(),
            'openTickets' => DB::table('tickets')->whereNotIn('status', ['closed'])->count(),
        ];

        $registrationsChart = User::selectRaw("DATE(created_at) as d, COUNT(*) as c")
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('d')->orderBy('d')->get();

        $recentUsers = User::latest('id')->take(6)->get();
        $recentAudit = \App\Models\AuditLog::with('user')->latest('id')->take(8)->get();

        return view('dashboard.admin', compact('stats', 'registrationsChart', 'recentUsers', 'recentAudit'));
    }

    public function student()
    {
        $user = auth()->user();
        $reviewService = app(ReviewService::class);

        $todayPlan = \App\Models\PlanItem::query()
            ->where('user_id', $user->id)
            ->where('date', today())
            ->with('subject')
            ->get();

        $todayMinutes = (int) ($user->studySessions()->where('date', today())->sum('minutes'));

        $recentAttempts = $user->attempts()->where('status', 'finished')
            ->with('exam')->latest('finished_at')->take(5)->get();

        $stats = [
            'dueReviews' => $reviewService->pendingCount($user),
            'recentTests' => $user->attempts()->where('status', 'finished')->count(),
            'avgPercent' => round((float) ($user->attempts()->where('status', 'finished')->avg('percent') ?? 0), 1),
            'todayMinutes' => $todayMinutes,
        ];

        $mastery = app(\App\Services\MasteryService::class)->strengthsAndWeaknesses($user->id, 3);

        $enrollments = $user->enrollments()->with('course')->take(3)->get();
        $subscription = Subscription::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest('ends_at')->first();

        return view('dashboard.student', compact('stats', 'todayPlan', 'recentAttempts', 'mastery', 'enrollments', 'subscription', 'reviewService'));
    }

    public function counselor()
    {
        $user = auth()->user();
        $students = $user->students()->withCount(['attempts'])->get();
        $upcoming = \App\Models\CounselingSession::query()
            ->where('counselor_id', $user->id)
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')->take(5)->get();
        $openFollowups = \App\Models\FollowUp::query()
            ->where('counselor_id', $user->id)->where('status', 'open')->count();
        $assignedTickets = \App\Models\Ticket::query()
            ->where('assigned_to', $user->id)->whereNotIn('status', ['closed'])->count();

        return view('dashboard.counselor', compact('students', 'upcoming', 'openFollowups', 'assignedTickets'));
    }

    public function teacher()
    {
        $user = auth()->user();
        $courses = Course::query()->where('teacher_id', $user->id)->withCount('enrollments')->get();
        $pendingSubmissions = AssignmentSubmission::query()
            ->where('status', 'submitted')
            ->whereHas('assignment', fn ($q) => $q->where('created_by', $user->id))
            ->count();
        $classes = OnlineClass::query()
            ->where('teacher_id', $user->id)
            ->where('status', 'scheduled')
            ->orderBy('starts_at')->take(5)->get();

        return view('dashboard.teacher', compact('courses', 'pendingSubmissions', 'classes'));
    }
}
