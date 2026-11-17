<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionExpiringNotification;
use Illuminate\Console\Command;

/**
 * انقضای اشتراک‌ها: علامت‌گذاری منقضی‌شده‌ها و هشدار ۳ روز مانده
 */
class ExpireSubscriptions extends Command
{
    protected $signature = 'key:expire-subscriptions';

    protected $description = 'انقضای اشتراک‌های گذشته و ارسال هشدار سه روز مانده';

    public function handle(): int
    {
        // ۱) هشدار سه روز مانده (فقط یک‌بار)
        $expiring = Subscription::query()
            ->with('user')
            ->where('status', 'active')
            ->whereBetween('ends_at', [now()->startOfDay(), now()->addDays(3)->endOfDay()])
            ->get();

        $warned = 0;
        foreach ($expiring as $subscription) {
            $daysLeft = (int) now()->startOfDay()->diffInDays($subscription->ends_at->startOfDay());
            if (! $subscription->user || ! $subscription->user->is_active) {
                continue;
            }
            $already = $subscription->user->notifications()
                ->where('type', SubscriptionExpiringNotification::class)
                ->where('created_at', '>=', now()->subDay())
                ->whereJsonContains("data->subscription_id", $subscription->id)
                ->exists();
            if ($already) {
                continue;
            }
            $subscription->user->notify(new SubscriptionExpiringNotification($subscription, $daysLeft));
            $warned++;
        }

        // ۲) منقضی‌سازی
        $expired = Subscription::query()
            ->where('status', 'active')
            ->where('ends_at', '<=', now())
            ->update(['status' => 'expired']);

        $this->info("اشتراک‌های منقضی‌شده: {$expired} — هشدارهای ارسال‌شده: {$warned}");
        \App\Models\AuditLog::record(null, 'schedule.expire-subscriptions', null, [
            'expired' => $expired,
            'warned' => $warned,
        ]);

        return self::SUCCESS;
    }
}
