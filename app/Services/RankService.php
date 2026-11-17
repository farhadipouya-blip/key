<?php

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\HistoricalResult;
use App\Models\MajorUniversity;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * تخمین رتبه بر اساس درصدها و اطلاعات تاریخی
 * مدل: ترکیب وزنی درصدهای دروس عمومی/اختصاصی با ضرایب کنکور
 * و کالیبراسیون با نتایج تاریخی خود کاربر (اگر موجود باشد)
 */
class RankService
{
    /** ضرایب پیش‌فرض گروه‌ها */
    public const GROUP_WEIGHTS = [
        'experimental' => ['general' => 0.40, 'special' => 0.60],
        'math' => ['general' => 0.40, 'special' => 0.60],
        'humanities' => ['general' => 0.40, 'special' => 0.60],
        'arts' => ['general' => 0.50, 'special' => 0.50],
        'language' => ['general' => 0.50, 'special' => 0.50],
    ];

    /**
     * تخمین رتبه از درصدها
     * input: [['title' => 'ریاضی', 'percent' => 78, 'is_special' => true], ...]
     */
    public function estimate(User $user, array $input, string $group = 'experimental'): array
    {
        $weights = self::GROUP_WEIGHTS[$group] ?? self::GROUP_WEIGHTS['experimental'];

        $generalAvg = $this->avgPercent($input, 'general');
        $specialAvg = $this->avgPercent($input, 'special');
        $composite = $generalAvg * $weights['general'] + $specialAvg * $weights['special'];

        // مدل پایه: رتبه کشوری ≈ تابع نمایی از درصد ترکیبی
        // rank ≈ 550000 * (1 - composite/100)^3.2 — کالیبره‌شده روی بازه‌های معمول کنکور
        $baseRank = (int) round(550000 * pow(max(0.01, 1 - $composite / 100), 3.2));

        // کالیبراسیون با اطلاعات تاریخی خود کاربر
        $calibration = $this->calibrate($user, $baseRank, $composite);
        $rank = $calibration['rank'];

        $percentile = max(0, min(100, 100 - ($rank / 600000 * 100)));

        $scenario = [
            'best' => (int) max(1, round($rank * 0.8)),
            'expected' => (int) max(1, $rank),
            'worst' => (int) max(1, round($rank * 1.25)),
        ];

        return [
            'rank' => $rank,
            'percentile' => round($percentile, 2),
            'composite_percent' => round($composite, 2),
            'scenario' => $scenario,
            'calibrated' => $calibration['used'],
        ];
    }

    private function avgPercent(array $input, string $kind): float
    {
        $values = collect($input)
            ->filter(fn ($row) => ($row['is_special'] ?? false) === ($kind === 'special'))
            ->pluck('percent')
            ->filter(fn ($p) => $p !== null && $p !== '')
            ->map(fn ($p) => (float) \App\Support\Jalali::toLatinDigits((string) $p))
            ->values();

        return $values->isEmpty() ? 0 : $values->avg();
    }

    /**
     * کالیبراسیون: اگر کاربر نتیجه تاریخی دارد، نرخ خطای مدل روی نتیجه واقعی او
     * به‌عنوان ضریب تصحیح اعمال می‌شود.
     */
    private function calibrate(User $user, int $baseRank, float $composite): array
    {
        $latest = HistoricalResult::query()
            ->where('user_id', $user->id)
            ->whereNotNull('rank')
            ->orderByDesc('year')
            ->first();

        if (! $latest || $composite <= 0) {
            return ['rank' => $baseRank, 'used' => false];
        }

        // پیش‌بینی مدل برای نتیجه واقعی قبلی
        $modelPredicted = 550000 * pow(max(0.01, 1 - (float) $latest->total_percent / 100), 3.2);
        if ($modelPredicted <= 0) {
            return ['rank' => $baseRank, 'used' => false];
        }

        $factor = max(0.5, min(2.0, $latest->rank / $modelPredicted));
        return [
            'rank' => (int) max(1, round($baseRank * $factor)),
            'used' => true,
        ];
    }

    /** تحلیل شانس قبولی برای یک رتبه تخمینی */
    public function selectionAnalysis(int $estimatedRank, string $group, int $year): Collection
    {
        return MajorUniversity::query()
            ->where('year', $year)
            ->whereHas('major', fn ($q) => $q->where('group', $group))
            ->with(['major', 'university'])
            ->get()
            ->map(function (MajorUniversity $mu) use ($estimatedRank) {
                return [
                    'id' => $mu->id,
                    'university' => $mu->university->name,
                    'city' => $mu->university->city,
                    'major' => $mu->major->title,
                    'tuition_type' => $mu->tuition_type,
                    'last_rank_min' => $mu->last_rank_min,
                    'last_rank_max' => $mu->last_rank_max,
                    'chance' => $mu->chancePercent($estimatedRank),
                ];
            })
            ->sortByDesc('chance')
            ->values();
    }
}
