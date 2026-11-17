<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class PlanReminderNotification extends Notification
{
    public function __construct(
        public int $pendingItems,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'یادآوری برنامه مطالعاتی',
            'body' => 'امروز ' . fa_num($this->pendingItems) . ' جلسه مطالعه انجام‌نشده در برنامه شما باقی مانده است.',
            'url' => route_if('plan.index'),
            'icon' => 'bi-calendar2-week',
            'kind' => 'plan',
        ];
    }
}
