<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ReviewService;
use App\Services\SmsService;
use Illuminate\Console\Command;

/**
 * یادآوری مرور روزانه — ساعت ۸ صبح به دارندگان مرور سررسید
 */
class ReviewReminders extends Command
{
    protected $signature = 'key:review-reminders';

    protected $description = 'یادآوری مرورهای سررسید به کاربران (یادداشت در اعلان‌ها)';

    public function handle(): int
    {
        $service = app(ReviewService::class);

        $users = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->get();

        $notified = 0;
        foreach ($users as $user) {
            $due = $service->pendingCount($user);
            if ($due === 0) {
                continue;
            }

            $user->notify(new \App\Notifications\ReviewReminderNotification($due, 'review'));
            if ($user->phone) {
                app(SmsService::class)->send(
                    $user->phone,
                    'کلید: شما ' . fa_num($due) . ' مورد مرور سررسید دارید. زمان مرور امروز را از دست ندهید.',
                    $user->id,
                    'review-reminder'
                );
            }
            $notified++;
        }

        $this->info("یادآوری مرور برای {$notified} کاربر ثبت شد.");
        \App\Models\AuditLog::record(null, 'schedule.review-reminders', null, ['notified' => $notified]);

        return self::SUCCESS;
    }
}
