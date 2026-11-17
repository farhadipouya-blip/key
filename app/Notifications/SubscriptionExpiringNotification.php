<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SubscriptionExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
        public int $daysLeft,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'اشتراک شما رو به پایان است',
            'body' => 'اشتراک شما تا ' . fa_date($this->subscription->ends_at) . ' (' . fa_num($this->daysLeft) . ' روز دیگر) منقضی می‌شود. از بخش فروشگاه تمدید کنید.',
            'url' => route_if('shop.index'),
            'icon' => 'bi-hourglass-split',
            'subscription_id' => $this->subscription->id,
        ];
    }
}
