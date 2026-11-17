<?php

namespace App\Console\Commands;

use App\Models\PlanItem;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * یادآوری برنامه مطالعاتی روزانه — ساعت ۱۷ عصر
 */
class PlanReminders extends Command
{
    protected $signature = 'key:plan-reminders';

    protected $description = 'یادآوری آیتم‌های برنامه مطالعاتی انجام‌نشده امروز';

    public function handle(): int
    {
        $pendingUsers = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->whereHas('planItems', function ($q) {
                $q->where('date', today())
                    ->where('status', 'pending');
            })
            ->withCount(['planItems as pendingToday' => function ($q) {
                $q->where('date', today())
                    ->where('status', 'pending');
            }])
            ->get();

        $notified = 0;
        foreach ($pendingUsers as $user) {
            $user->notify(new \App\Notifications\PlanReminderNotification((int) $user->pendingToday));
            $notified++;
        }

        $this->info("یادآوری برنامه برای {$notified} کاربر ثبت شد.");
        \App\Models\AuditLog::record(null, 'schedule.plan-reminders', null, ['notified' => $notified]);

        return self::SUCCESS;
    }
}
