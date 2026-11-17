<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * سرویس پیامک با درایورهای کاوه‌نگار، SMS.ir، ملی‌پیامک و فراز
 * در صورت نبود کلید، پیام در وضعیت simulated ثبت می‌شود.
 */
class SmsService
{
    public function send(string $phone, string $body, ?int $userId = null, ?string $template = null): SmsMessage
    {
        $driver = (string) setting('sms.driver', config('services.sms.driver', 'log'));
        $provider = config('services.sms.providers.' . $driver . '.name', $driver);

        $message = SmsMessage::create([
            'user_id' => $userId,
            'phone' => $phone,
            'body' => $body,
            'template' => $template,
            'provider' => $provider,
            'status' => 'pending',
        ]);

        // صف ارسال: ارسال هم‌زمان روی درایور log انجام می‌شود
        if ($driver === 'log') {
            $this->dispatch($message, $driver);
        } else {
            SendSmsJob::dispatch($message->id);
        }

        return $message;
    }

    /** ارسال واقعی (در Job یا هم‌زمان) */
    public function dispatch(SmsMessage $message, ?string $driverOverride = null): bool
    {
        $driver = $driverOverride ?? (string) setting('sms.driver', config('services.sms.driver', 'log'));
        $apiKey = (string) setting('sms.' . $driver . '_key', '');
        $sender = (string) setting('sms.sender_number', '');

        $ok = false;
        $ref = null;
        $error = null;

        try {
            switch ($driver) {
                case 'log':
                case 'simulated':
                    Log::info("[SMS SIMULATED] {$message->phone}: {$message->body}");
                    $ok = true;
                    break;

                case 'kavenegar':
                    $res = Http::asForm()->post("https://api.kavenegar.com/v1/{$apiKey}/sms/send.json", [
                        'receptor' => $message->phone,
                        'message' => $message->body,
                        'sender' => $sender,
                    ]);
                    $ok = $res->successful();
                    $ref = $res->json('return.status') ?: null;
                    if (! $ok) {
                        $error = 'Kavenegar error: ' . $res->body();
                    }
                    break;

                case 'smsir':
                    $token = Http::post('https://api.sms.ir/v1/token', [
                        'apiKey' => $apiKey,
                        'secretKey' => setting('sms.smsir_secret', ''),
                    ])->json('data');
                    $res = Http::withToken((string) $token)->post('https://api.sms.ir/v1/send/bulk', [
                        'lineNumber' => $sender,
                        'messageText' => $message->body,
                        'mobiles' => [$message->phone],
                    ]);
                    $ok = $res->successful();
                    if (! $ok) {
                        $error = 'SMS.ir error: ' . $res->body();
                    }
                    break;

                case 'mellipayamak':
                    $res = Http::asForm()->post('https://rest.payamak-panel.com/api/SendSMS/SendSMS', [
                        'username' => setting('sms.melli_username', ''),
                        'password' => setting('sms.melli_password', ''),
                        'to' => $message->phone,
                        'from' => $sender,
                        'text' => $message->body,
                    ]);
                    $ok = $res->successful();
                    $ref = $res->json('RetStatus') ?: null;
                    if (! $ok) {
                        $error = 'MelliPayamak error: ' . $res->body();
                    }
                    break;

                case 'faraz':
                    $res = Http::withHeaders(['apikey' => $apiKey])
                        ->post('https://api2.ippanel.com/api/v1/sms/send/webservice/single', [
                            'recipient' => [$message->phone],
                            'sender' => $sender,
                            'message' => $message->body,
                        ]);
                    $ok = $res->successful();
                    if (! $ok) {
                        $error = 'Faraz error: ' . $res->body();
                    }
                    break;

                default:
                    Log::info("[SMS UNKNOWN DRIVER {$driver}] {$message->phone}: {$message->body}");
                    $ok = true;
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $message->forceFill([
            'status' => $ok ? 'sent' : 'failed',
            'provider_ref' => $ref,
            'error' => $error,
            'sent_at' => $ok ? now() : null,
        ])->save();

        return $ok;
    }
}
