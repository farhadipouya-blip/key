<?php

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Review;
use App\Models\ReviewLog;
use App\Models\Summary;
use App\Models\User;

/**
 * سرویس مرور فاصله‌دار بر پایه الگوریتم SM-2
 * کیفیت‌ها: 1=دوباره | 2=سخت | 3=خوب | 4=آسان
 */
class ReviewService
{
    public const QUALITY_AGAIN = 1;
    public const QUALITY_HARD = 2;
    public const QUALITY_GOOD = 3;
    public const QUALITY_EASY = 4;

    public const QUALITY_MAP = [
        self::QUALITY_AGAIN => 'دوباره',
        self::QUALITY_HARD => 'سخت',
        self::QUALITY_GOOD => 'خوب',
        self::QUALITY_EASY => 'آسان',
    ];

    /**
     * محاسبه چرخه بعدی بر اساس SM-2
     * EF جدید = EF + (0.1 - (5 - q) * (0.08 + (5 - q) * 0.02)) با q نگاشت ۱..۴ به مقیاس ۲..۵
     */
    public function schedule(Review $review, int $quality): Review
    {
        $q = match ($quality) {
            self::QUALITY_AGAIN => 2, // ضعیف‌ترین پاسخ در SM-2
            self::QUALITY_HARD => 3,
            self::QUALITY_GOOD => 4,
            self::QUALITY_EASY => 5,
            default => 3,
        };

        $ef = max(1.3, $review->easiness + (0.1 - (5 - $q) * (0.08 + (5 - $q) * 0.02)));
        $intervalBefore = $review->interval_days;

        if ($quality === self::QUALITY_AGAIN) {
            // فراموشی: بازگشت به ابتدای چرخه
            $interval = 1;
            $review->repetitions = 0;
            $review->lapses += 1;
        } else {
            $rep = $review->repetitions + 1;
            $interval = match ($rep) {
                1 => 1,
                2 => $quality === self::QUALITY_HARD ? 3 : 6,
                default => (int) max($review->interval_days + 1, round($review->interval_days * $ef * ($quality === self::QUALITY_EASY ? 1.3 : ($quality === self::QUALITY_HARD ? 0.6 : 1)))),
            };
            $review->repetitions = $rep;
        }

        $review->easiness = round($ef, 2);
        $review->interval_days = (int) $interval;
        $review->total_reviews += 1;
        $review->last_reviewed_at = now();
        $review->due_at = now()->addDays($interval);
        $review->save();

        ReviewLog::create([
            'review_id' => $review->id,
            'user_id' => $review->user_id,
            'quality' => $quality,
            'interval_before' => $intervalBefore,
            'interval_after' => $interval,
            'easiness_after' => $review->easiness,
        ]);

        return $review;
    }

    /**
     * افزودن یا بازیابی رکورد مرور برای یک آیتم
     */
    public function ensureReview(User $user, Question|Summary $item): Review
    {
        $type = $item instanceof Question ? Question::class : Summary::class;

        return Review::firstOrCreate([
            'user_id' => $user->id,
            'reviewable_type' => $type,
            'reviewable_id' => $item->id,
        ], [
            'status' => 'active',
            'easiness' => 2.5,
            'interval_days' => 0,
            'due_at' => now(),
        ]);
    }

    /** صف مرور امروز: موارد سررسید + عقب‌افتاده */
    public function dailyQueue(User $user, int $limit = 20)
    {
        return Review::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now())
            ->with('reviewable')
            ->orderBy('due_at')
            ->limit($limit)
            ->get();
    }

    public function pendingCount(User $user): int
    {
        return Review::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now())
            ->count();
    }

    /** اتصال آزمون به مرور: سؤالات غلط و نزده به صف مرور اضافه می‌شوند */
    public function syncFromExam(ExamAttempt $attempt): int
    {
        $user = $attempt->user;
        $answers = $attempt->answers()->get();
        $count = 0;

        foreach ($answers as $answer) {
            // پاسخ غلط یا نزده (is_correct false/0/null)
            if (! $answer->is_correct && $answer->is_correct !== true) {
                $question = Question::find($answer->question_id);
                if ($question && $question->type === 'multi') {
                    $this->ensureReview($user, $question);
                    $count++;
                }
            }
        }

        return $count;
    }

    /** آمار ماندگاری مطالب (نسبت پاسخ خوب/آسان در بازه‌های اخیر) */
    public function retentionStats(User $user): array
    {
        $logs = ReviewLog::query()->where('user_id', $user->id)
            ->where('reviewed_at', '>=', now()->subDays(30))
            ->get();

        $total = $logs->count();
        $good = $logs->whereIn('quality', [self::QUALITY_GOOD, self::QUALITY_EASY])->count();

        return [
            'total' => $total,
            'retention' => percent($good, $total),
        ];
    }
}
