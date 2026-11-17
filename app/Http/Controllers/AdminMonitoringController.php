<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * پایش زنده سامانه: آنلاین‌ها، ثبت‌نام‌ها، فعالیت اخیر، صف جاب‌ها، پیامک‌ها
 */
class AdminMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $onlineUsers = User::query()
            ->where('last_seen_at', '>=', now()->subMinutes(5))
            ->orderByDesc('last_seen_at')
            ->get(['id', 'name', 'email', 'last_seen_at']);

        $stats = [
            'online' => $onlineUsers->count(),
            'today_registrations' => User::query()->whereDate('created_at', today())->count(),
            'week_registrations' => User::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'total_users' => User::query()->count(),
            'jobs_pending' => \DB::table('jobs')->count(),
            'failed_jobs' => \DB::table('failed_jobs')->count(),
        ];

        $recentActivity = AuditLog::query()
            ->with('user:id,name')
            ->orderByDesc('id')
            ->take(15)
            ->get();

        $recentSms = SmsMessage::query()
            ->with('user:id,name')
            ->orderByDesc('id')
            ->take(10)
            ->get();

        // نمودار ثبت‌نام ۱۴ روز اخیر
        $registrationsChart = \DB::table('users')
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        // عملکرد کارکنان: تعداد رخدادهای ثبت‌شده در لاگ + تولید محتوا (بدون وابستگی به رابطه مدل)
        $staffQuery = User::query()
            ->whereHas('roles', fn ($r) => $r->whereNotIn('slug', ['student']))
            ->with('roles:id,name,slug');

        $staffPerformance = $staffQuery->get()->map(function (User $staff) {
            $staff->logs_count = \DB::table('audit_logs')->where('user_id', $staff->id)->count();
            $staff->questions_count = \DB::table('questions')->where('created_by', $staff->id)->count();
            $staff->imports_count = \DB::table('file_imports')->where('user_id', $staff->id)->count();
            $staff->media_count = \DB::table('media')->where('user_id', $staff->id)->count();

            return $staff;
        })->sortByDesc('logs_count')->take(12)->values();

        return view('admin.monitoring.index', compact(
            'onlineUsers', 'stats', 'recentActivity', 'recentSms', 'registrationsChart', 'staffPerformance'
        ));
    }
}
