<?php

namespace App\Jobs;

use App\Models\SmsMessage;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $smsMessageId) {}

    public function handle(): void
    {
        $message = SmsMessage::find($this->smsMessageId);
        if (! $message || in_array($message->status, ['sent', 'simulated'])) {
            return;
        }
        app(SmsService::class)->dispatch($message);
    }
}
