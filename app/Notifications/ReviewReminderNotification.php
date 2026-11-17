<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ReviewReminderNotification extends Notification
{
    public function __construct(
        public int $dueCount,
        public string $kind = 'review',
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'یادآوری مرور هوشمند',
            'body' => 'شما ' . fa_num($this->dueCount) . ' مورد مرور سررسید دارید. امروز چند دقیقه برای مرور وقت بگذارید.',
            'url' => route_if('review.index'),
            'icon' => 'bi-arrow-repeat',
            'kind' => $this->kind,
        ];
    }
}
