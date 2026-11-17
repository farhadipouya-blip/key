<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * سرویس هوش مصنوعی (اختیاری) با درایور OpenAI و Google Gemini
 * نبود کلید باعث توقف سایر بخش‌ها نمی‌شود.
 */
class AiService
{
    public function available(): bool
    {
        $provider = (string) setting('ai.provider', config('services.ai.provider', 'none'));
        if ($provider === 'none') {
            return false;
        }
        if ($provider === 'openai') {
            return (bool) setting('ai.openai_key', config('services.ai.openai_key'));
        }
        if ($provider === 'gemini') {
            return (bool) setting('ai.gemini_key', config('services.ai.gemini_key'));
        }
        return false;
    }

    public function provider(): string
    {
        return (string) setting('ai.provider', config('services.ai.provider', 'none'));
    }

    /**
     * تولید پاسخ از لیست پیام‌ها [['role' => 'user'|'assistant'|'system', 'content' => ...]]
     */
    public function chat(array $messages, ?string $model = null): ?string
    {
        if (! $this->available()) {
            return null;
        }

        $model = $model ?: (string) setting('ai.model', config('services.ai.model', 'gpt-4o-mini'));

        try {
            if ($this->provider() === 'openai') {
                $key = setting('ai.openai_key', config('services.ai.openai_key'));
                $res = Http::withToken((string) $key)
                    ->timeout(60)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $model,
                        'messages' => $messages,
                        'temperature' => 0.6,
                    ]);
                return $res->json('choices.0.message.content');
            }

            if ($this->provider() === 'gemini') {
                $key = setting('ai.gemini_key', config('services.ai.gemini_key'));
                $system = collect($messages)->firstWhere('role', 'system')['content'] ?? null;
                $contents = collect($messages)
                    ->where('role', '!=', 'system')
                    ->map(fn ($m) => [
                        'role' => $m['role'] === 'assistant' ? 'model' : 'user',
                        'parts' => [['text' => $m['content']]],
                    ])->values()->all();

                $res = Http::timeout(60)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}", array_filter([
                        'contents' => $contents,
                        'systemInstruction' => $system ? ['parts' => [['text' => $system]]] : null,
                    ]));
                return $res->json('candidates.0.content.parts.0.text');
            }
        } catch (\Throwable $e) {
            Log::warning('AI service error: ' . $e->getMessage());
        }

        return null;
    }

    /** پیشنهاد پاسخ تیکت برای پشتیبان */
    public function suggestTicketReply(string $subject, string $body): ?string
    {
        return $this->chat([
            ['role' => 'system', 'content' => 'تو کارشناس پشتیبانی پلتفرم آموزشی کلید هستی. پاسخ کوتاه، مودبانه و فارسی بنویس.'],
            ['role' => 'user', 'content' => "موضوع تیکت: {$subject}\n\nمتن تیکت:\n{$body}\n\nیک پاسخ مناسب پیشنهاد بده."],
        ]);
    }

    /** تحلیل عملکرد دانشجو و پیشنهاد برنامه */
    public function analyzePerformance(array $performanceData): ?string
    {
        $data = json_encode($performanceData, JSON_UNESCAPED_UNICODE);
        return $this->chat([
            ['role' => 'system', 'content' => 'تو مربی هوشمند مطالعه هستی. تحلیل کوتاه و اقدام‌محور فارسی ارائه بده.'],
            ['role' => 'user', 'content' => "داده عملکرد دانشجو:\n{$data}\n\nتحلیل و ۳ اقدام پیشنهادی بده."],
        ]);
    }
}
