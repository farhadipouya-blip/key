<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSuccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('پرداخت موفق — کلید')
            ->greeting('سلام ' . $notifiable->name)
            ->line('پرداخت سفارش ' . $this->order->number . ' با موفقیت انجام شد.')
            ->line('مبلغ: ' . money($this->order->payable))
            ->action('مشاهده سفارش', route('shop.invoice', $this->order->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'پرداخت موفق',
            'body' => 'سفارش ' . $this->order->number . ' با مبلغ ' . money($this->order->payable) . ' پرداخت شد و دسترسی‌ها فعال گردید.',
            'url' => route('shop.invoice', $this->order->id),
            'icon' => 'bi-check-circle',
        ];
    }
}
