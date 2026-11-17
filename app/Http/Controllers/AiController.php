<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\ExamAttempt;
use App\Models\StudySession;
use App\Services\AiService;
use App\Services\MasteryService;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * ماژول دستیار هوش مصنوعی: چت مربی مطالعه، گزارش عملکرد، پیشنهاد پاسخ تیکت
 * اگر کلید API تنظیم نشده باشد، ماژول با پیام محترمانه فارسی کار می‌کند و چیزی نمی‌شکند.
 */
class AiController extends Controller
{
    /** پرامپت سیستم مربی مطالعه */
    private const COACH_PROMPT = 'تو «مربی کلید» هستی؛ مربی هوشمند مطالعه دانش‌آموزان کنکور ایران. همیشه فارسی، کوتاه، دقیق و اقدام‌محور پاسخ بده. در برنامه‌ریزی مطالعه، روش مرور فاصله‌دار، انگیزه و تکنیک‌های آزمون‌دادن کمک کن.';

    public function __construct(
        private AiService $ai,
        private MasteryService $mastery,
        private ReviewService $reviews,
    ) {}

    /** صفحه چت: فهرست گفتگوها + پیام‌های گفتگوی انتخابی */
    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = AiConversation::query()
            ->where('user_id', $user->id)
            ->withCount('messages')
            ->latest('updated_at')
            ->take(30)
            ->get();

        $conversation = null;
        if ($request->filled('c')) {
            $conversation = AiConversation::query()
                ->where('user_id', $user->id)
                ->find($request->integer('c'));
        }
        $conversation ??= $conversations->first();

        $messages = $conversation
            ? $conversation->messages()->oldest()->take(60)->get()
            : collect();

        return view('ai.index', compact('conversations', 'conversation', 'messages'))
            ->with('aiReady', $this->ai->available());
    }

    /** ارسال پیام و دریافت پاسخ (fetch JSON یا redirect معمولی) */
    public function chat(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:4000'],
            'conversation_id' => ['nullable', 'integer'],
        ], [
            'message.required' => 'متن پیام الزامی است.',
            'message.min' => 'متن پیام باید حداقل ۲ کاراکتر باشد.',
            'message.max' => 'متن پیام حداکثر ۴۰۰۰ کاراکتر است.',
        ]);

        // گفتگوی موجود فقط اگر مالک آن باشد
        $conversation = null;
        if (! empty($data['conversation_id'])) {
            $conversation = AiConversation::query()->find($data['conversation_id']);
            if ($conversation && $conversation->user_id !== $user->id) {
                abort(403, 'شما فقط به گفتگوهای خودتان دسترسی دارید.');
            }
        }

        $conversation ??= AiConversation::create([
            'user_id' => $user->id,
            'title' => Str::limit($data['message'], 40),
            'context_type' => 'coach',
        ]);

        $userMessage = AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $data['message'],
        ]);

        $reply = $this->generateChatReply($conversation);

        $assistantMessage = AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $reply,
        ]);

        $conversation->touch();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'conversation_id' => $conversation->id,
                'available' => $this->ai->available(),
                'user_message' => [
                    'id' => $userMessage->id,
                    'role' => 'user',
                    'content' => $userMessage->content,
                    'time' => fa_datetime($userMessage->created_at),
                ],
                'reply' => [
                    'id' => $assistantMessage->id,
                    'role' => 'assistant',
                    'content' => $assistantMessage->content,
                    'time' => fa_datetime($assistantMessage->created_at),
                ],
            ]);
        }

        return redirect()->route('ai.index', ['c' => $conversation->id]);
    }

    /** صفحه گزارش عملکرد (تولید با AI یا قانون‌محور محلی) */
    public function report(Request $request)
    {
        $user = $request->user();

        $performance = $this->collectPerformance($user);

        $text = null;
        if ($this->ai->available()) {
            $text = $this->ai->analyzePerformance($performance['data']);
        }
        $usedAi = $text !== null;

        if (! $usedAi) {
            $text = $this->buildLocalReport($performance['data']);
        }

        // ذخیره به‌عنوان پیام گزارش در گفتگوی اختصاصی
        $conversation = AiConversation::query()
            ->where('user_id', $user->id)
            ->where('context_type', 'report')
            ->first();

        $message = AiMessage::create([
            'ai_conversation_id' => ($conversation ??= AiConversation::create([
                'user_id' => $user->id,
                'title' => 'گزارش عملکرد',
                'context_type' => 'report',
            ]))->id,
            'role' => 'assistant',
            'content' => $text,
        ]);

        return view('ai.report', [
            'message' => $message,
            'conversation' => $conversation,
            'performance' => $performance['data'],
            'usedAi' => $usedAi,
        ]);
    }

    /** پیشنهاد پاسخ تیکت (JSON) — برای میز پشتیبانی */
    public function ticketSuggest(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
        ], [
            'subject.required' => 'موضوع تیکت الزامی است.',
            'body.required' => 'متن تیکت الزامی است.',
        ]);

        $suggestion = null;
        if ($this->ai->available()) {
            $suggestion = $this->ai->suggestTicketReply($data['subject'], $data['body']);
        }

        $usedAi = $suggestion !== null;
        if (! $usedAi) {
            $suggestion = $this->cannedTicketReply($data['subject']);
        }

        return response()->json([
            'ok' => true,
            'ai' => $usedAi,
            'suggestion' => $suggestion,
        ]);
    }

    // ------------------------------------------------------------------
    // متدهای کمکی
    // ------------------------------------------------------------------

    /** تولید پاسخ چت: با سرویس AI یا پیام محترمانه تنظیم‌نشده */
    private function generateChatReply(AiConversation $conversation): string
    {
        if (! $this->ai->available()) {
            return 'دستیار هوش مصنوعی هنوز تنظیم نشده است؛ برای فعال‌سازی، کلید API را در تنظیمات سامانه (بخش هوش مصنوعی) وارد کنید. '
                . 'تا آن زمان می‌توانید از گزارش عملکرد قانون‌محور و صفحه تحلیل عملکرد استفاده کنید؛ دیگر بخش‌های سامانه بدون AI به‌طور کامل کار می‌کنند.';
        }

        $history = $conversation->messages()
            ->orderByDesc('id')
            ->take(10)
            ->get()
            ->sortBy('id')
            ->map(fn (AiMessage $m) => ['role' => $m->role === 'assistant' ? 'assistant' : 'user', 'content' => $m->content])
            ->values()
            ->all();

        $messages = array_merge(
            [['role' => 'system', 'content' => self::COACH_PROMPT]],
            $history
        );

        $reply = $this->ai->chat($messages);

        return $reply ?: 'پاسخ از سرویس هوش مصنوعی دریافت نشد؛ لطفاً چند لحظه بعد دوباره تلاش کنید.';
    }

    /** جمع‌آوری داده واقعی عملکرد برای گزارش */
    private function collectPerformance($user): array
    {
        $attempts = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'finished')
            ->whereNotNull('percent')
            ->orderBy('started_at')
            ->get();

        $mastery = $this->mastery->strengthsAndWeaknesses($user->id, 5);

        $studyMinutes = (int) StudySession::query()
            ->where('user_id', $user->id)
            ->where('date', '>=', now()->subDays(13)->toDateString())
            ->sum('minutes');

        $retention = $this->reviews->retentionStats($user);
        $overdue = $this->reviews->pendingCount($user);

        $data = [
            'user' => ['name' => $user->name, 'grade' => $user->grade, 'field_group' => $user->field_group],
            'attempts_count' => $attempts->count(),
            'avg_percent' => $attempts->isEmpty() ? 0 : round((float) $attempts->avg('percent'), 1),
            'best_percent' => $attempts->isEmpty() ? 0 : round((float) $attempts->max('percent'), 1),
            'last_percent' => $attempts->isEmpty() ? 0 : round((float) $attempts->last()->percent, 1),
            'recent_trend' => $attempts->take(-8)->map(fn ($a) => round((float) $a->percent, 1))->values()->all(),
            'strengths' => collect($mastery['strengths'])->map(fn ($r) => ['subject' => $r->subject?->title, 'level' => round((float) $r->level)])->all(),
            'weaknesses' => collect($mastery['weaknesses'])->map(fn ($r) => ['subject' => $r->subject?->title, 'level' => round((float) $r->level)])->all(),
            'study_minutes_14d' => $studyMinutes,
            'review_retention_percent' => $retention['retention'],
            'overdue_reviews' => $overdue,
            'generated_at' => fa_datetime(now()),
        ];

        return ['data' => $data];
    }

    /** گزارش قانون‌محور محلی (وقتی AI تنظیم نشده) */
    private function buildLocalReport(array $d): string
    {
        $line = str_repeat('—', 34) . "\n";

        $text = "گزارش تحلیل عملکرد (قانون‌محور)\n{$line}";
        $text .= 'دانشجو: ' . $d['user']['name'];
        if ($d['user']['grade']) {
            $text .= ' — پایه ' . fa_num($d['user']['grade']);
        }
        if ($d['user']['field_group']) {
            $text .= ' — گروه ' . $d['user']['field_group'];
        }
        $text .= "\nتاریخ گزارش: " . $d['generated_at'] . "\n{$line}";

        // آزمون‌ها
        if ($d['attempts_count'] > 0) {
            $trend = $d['recent_trend'];
            $direction = 'ثابت';
            if (count($trend) >= 2) {
                $firstHalf = array_sum(array_slice($trend, 0, intdiv(count($trend), 2)));
                $secondHalf = array_sum(array_slice($trend, intdiv(count($trend), 2)));
                $direction = $secondHalf > $firstHalf ? 'صعودی' : ($secondHalf < $firstHalf ? 'نزولی' : 'ثابت');
            }
            $text .= "۱) آزمون‌ها\n";
            $text .= '• تعداد آزمون تمام‌شده: ' . fa_num($d['attempts_count']) . "\n";
            $text .= '• میانگین درصد: ' . fa_num($d['avg_percent']) . '٪ | بهترین: ' . fa_num($d['best_percent']) . '٪ | آخرین: ' . fa_num($d['last_percent']) . "٪\n";
            $text .= '• روند ۸ آزمون اخیر: ' . $direction . " — با تمرین منظم این روند را تثبیت کن.\n\n";
        } else {
            $text .= "۱) آزمون‌ها\n• هنوز آزمونی نداده‌ای؛ از آزمون‌های مبحثی شروع کن تا داده تحلیل ایجاد شود.\n\n";
        }

        // تسلط
        $text .= "۲) تسلط دروس\n";
        if (! empty($d['strengths'])) {
            $strong = collect($d['strengths'])->take(3)->map(fn ($s) => $s['subject'] . ' (' . fa_num($s['level']) . '٪)')->implode('، ');
            $text .= '• نقاط قوت: ' . $strong . "\n";
        }
        if (! empty($d['weaknesses'])) {
            $weak = collect($d['weaknesses'])->take(3)->map(fn ($s) => $s['subject'] . ' (' . fa_num($s['level']) . '٪)')->implode('، ');
            $text .= '• نقاط ضعف: ' . $weak . "\n";
            $text .= "• اقدام: برای هر درس ضعیف، این هفته ۳ جلسه ۴۵ دقیقه‌ای تست مبحثی + تحلیل اشتباهات بگذار.\n";
        } else {
            $text .= "• داده تسلطی ثبت نشده؛ با شرکت در آزمون‌ها سطوح تسلط محاسبه می‌شود.\n";
        }

        // مطالعه
        $daily = round($d['study_minutes_14d'] / 14);
        $text .= "\n۳) مطالعه و مرور\n";
        $text .= '• مطالعه ۱۴ روز اخیر: ' . duration_human($d['study_minutes_14d']) . ' (میانگین روزانه ' . duration_human($daily) . ")\n";
        if ($daily < 60) {
            $text .= "• اقدام: میانگین روزانه کم است؛ هدف هفته آینده را حداقل ۹۰ دقیقه در روز بگذار.\n";
        }
        $text .= '• ماندگاری مرور ۳۰ روز اخیر: ' . fa_num($d['review_retention_percent']) . "٪\n";
        if ($d['overdue_reviews'] > 0) {
            $text .= '• مرورهای عقب‌افتاده: ' . fa_num($d['overdue_reviews']) . " مورد — اول صبح همه را مرور کن، بعد سراغ مطلب جدید برو.\n";
        } else {
            $text .= "• صف مرور امروز خالی است؛ آفرین!\n";
        }

        $text .= "\n{$line}برای تحلیل هوشمندتر، دستیار هوش مصنوعی را در تنظیمات فعال کنید.";
        return $text;
    }

    /** پاسخ آماده محترمانه برای تیکت‌ها وقتی AI تنظیم نیست */
    private function cannedTicketReply(string $subject): string
    {
        return "با سلام و احترام\n"
            . "از تماس شما با پشتیبانی سامانه کلید و مطرح کردن موضوع «{$subject}» سپاسگزاریم.\n\n"
            . "درخواست شما بررسی شد. برای پیگیری سریع‌تر لطفاً موارد زیر را برای ما ارسال کنید:\n"
            . "۱) تصویری از مشکل یا شماره آزمون/دوره مرتبط؛\n"
            . "۲) نام مرورگر و زمان دقیق وقوع مشکل؛\n"
            . "۳) در صورت خطای پرداخت، شماره پیگیری تراکنش.\n\n"
            . "کارشناسان ما حداکثر تا یک روز کاری پاسخ‌گوی شما خواهند بود.\n"
            . "با احترام — تیم پشتیبانی کلید";
    }
}
