<?php

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\MasteryLevel;
use Illuminate\Support\Facades\DB;

/**
 * محاسبه سطح تسلط دانشجو در هر مبحث
 * ترکیب عملکرد آزمون‌های اخیر (وزن 70%) و ماندگاری مرور (وزن 30%)
 */
class MasteryService
{
    public function updateFromAttempt(ExamAttempt $attempt): void
    {
        $answers = $attempt->answers()->with('question')->get();

        foreach ($answers->groupBy(fn ($a) => $a->question->subject_id) as $subjectId => $group) {
            $this->recomputeSubject($attempt->user_id, $subjectId);
        }
    }

    public function recomputeSubject(int $userId, int $subjectId): void
    {
        // عملکرد آزمون‌ها در ۹۰ روز اخیر روی این مبحث
        $stats = DB::table('attempt_answers as aa')
            ->join('exam_attempts as at', 'at.id', '=', 'aa.attempt_id')
            ->join('questions as q', 'q.id', '=', 'aa.question_id')
            ->where('at.user_id', $userId)
            ->where('at.status', 'finished')
            ->where('q.subject_id', $subjectId)
            ->where('at.started_at', '>=', now()->subDays(90))
            ->selectRaw("SUM(CASE WHEN aa.is_correct = 1 THEN 1 ELSE 0 END) as correct,
                         SUM(CASE WHEN aa.is_correct IS NOT NULL THEN 1 ELSE 0 END) as attempted,
                         MAX(at.started_at) as last_at")
            ->first();

        $attempted = (int) ($stats->attempted ?? 0);
        $correct = (int) ($stats->correct ?? 0);

        // ماندگاری مرور
        $reviewStats = app(ReviewService::class)->retentionStats(\App\Models\User::find($userId));
        $retention = $reviewStats['retention'];

        $accuracy = $attempted > 0 ? $correct / $attempted * 100 : 0;
        $level = $accuracy * 0.7 + ($retention ?: 0) * 0.3;
        // کاهش با گذر زمان: هر ۳۰ روز بی‌فعالیت ۱۰٪ افت
        if ($stats->last_at) {
            $days = now()->diffInDays($stats->last_at);
            $level *= max(0.5, 1 - ($days / 300));
        }

        MasteryLevel::updateOrCreate(
            ['user_id' => $userId, 'subject_id' => $subjectId],
            [
                'level' => round($level, 2),
                'attempted' => $attempted,
                'correct' => $correct,
                'retention' => $retention,
                'updated_at' => now(),
            ]
        );
    }

    /** نقاط قوت و ضعف دانشجو */
    public function strengthsAndWeaknesses(int $userId, int $limit = 5): array
    {
        $rows = MasteryLevel::query()
            ->where('user_id', $userId)
            ->where('attempted', '>', 0)
            ->with('subject')
            ->get();

        $sorted = $rows->sortByDesc('level')->values();

        return [
            'strengths' => $sorted->take($limit)->values()->all(),
            'weaknesses' => $sorted->slice(max(0, $rows->count() - $limit))->values()->all(),
        ];
    }
}
