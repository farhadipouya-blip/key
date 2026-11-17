<?php

namespace App\Notifications;

use App\Models\CounselingSession;
use App\Support\Jalali;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * اعلان جلسه مشاوره به دانشجو (کانال database)
 */
class CounselingScheduledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public CounselingSession $session,
        public string $kind = 'scheduled', // scheduled|updated|done|canceled
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $when = Jalali::format($this->session->scheduled_at, 'Y/m/d — H:i');

        return match ($this->kind) {
            'done' => [
                'title' => 'خلاصه جلسه مشاوره ثبت شد',
                'body' => 'جلسه ' . $when . ' برگزار شد و خلاصه/توصیه‌های جلسه ثبت گردید.',
                'url' => route('dashboard'),
                'icon' => 'bi-journal-check',
            ],
            'canceled' => [
                'title' => 'جلسه مشاوره لغو شد',
                'body' => 'جلسه مشاوره ' . $when . ' لغو شد. برای تعیین زمان جدید با مشاور خود در تماس باشید.',
                'url' => route('dashboard'),
                'icon' => 'bi-calendar-x',
            ],
            'updated' => [
                'title' => 'جلسه مشاوره به‌روزرسانی شد',
                'body' => 'جزئیات جلسه مشاوره ' . $when . ' به‌روزرسانی شد.',
                'url' => route('dashboard'),
                'icon' => 'bi-calendar-event',
            ],
            default => [
                'title' => 'جلسه مشاوره جدید ثبت شد',
                'body' => 'جلسه مشاوره شما برای ' . $when . ' تعیین شد.',
                'url' => route('dashboard'),
                'icon' => 'bi-calendar-heart',
            ],
        };
    }
}
