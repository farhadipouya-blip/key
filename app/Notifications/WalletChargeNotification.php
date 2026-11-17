<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WalletChargeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public float $amount, public string $ref) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'شارژ کیف پول',
            'body' => 'کیف پول شما به مبلغ ' . money($this->amount) . ' شارژ شد. کد پیگیری: ' . $this->ref,
            'url' => route('shop.wallet'),
            'icon' => 'bi-wallet2',
        ];
    }
}
