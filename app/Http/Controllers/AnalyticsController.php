<?php

namespace App\Http\Controllers;

use App\Models\AttemptAnswer;
use App\Models\ExamAttempt;
use App\Models\StudySession;
use App\Models\Subject;
use App\Models\User;
use App\Services\MasteryService;
use App\Services\ReviewService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * ماژول تحلیل عملکرد: روند میانگین آزمون، دقت هر درس، تسلط، مطالعه، مرور و پیشنهادهای اصلاحی
 */
class AnalyticsController extends Controller
{
    public function __construct(
        private MasteryService $mastery,
        private ReviewService $reviews,
    ) {}

    /** تحلیل عملکرد خود کاربر */
    public function index()
    {
        $target = auth()->user();
        $data = $this->buildAnalytics($target);

        return view('analytics.index', array_merge(['target' => $target], $data));
    }

    /** تحلیل دانشجوی خاص — فقط مشاور/مدیر (counselor.panel یا users.manage) */
    public function user(User $user)
    {
        $me = auth()->user();

        if (! $me->canAccess('counselor.panel') && ! $me->canAccess('users.manage')) {
            abort(403, 'مشاهده تحلیل دانشجویان فقط برای مشاوران و مدیران مجاز است.');
        }

        $data = $this->buildAnalytics($user);

        return view('analytics.user', array_merge(['target' => $user], $data));
    }

    // ------------------------------------------------------------------
    // محاسبات تحلیل (مشترک بین index و user)
    // ------------------------------------------------------------------

    private function buildAnalytics(User $target): array
    {
        // نگاشت هر درس به درس ریشه (برای دقت هر درس اصلی)
        $subjects = Subject::all()->keyBy('id');
        $rootOf = function ($subjectId) use ($subjects) {
            $node = $subjects->get($subjectId);
            while ($node && $node->parent_id) {
                $node = $subjects->get($node->parent_id);
            }

            return $node;
        };

        // ۱) روند میانگین درصد آزمون‌های اخیر
        $attempts = ExamAttempt::query()
            ->where('user_id', $target->id)
            ->where('status', 'finished')
            ->whereNotNull('percent')
            ->orderBy('started_at')
            ->get();

        $trend = $attempts->take(-12)->map(fn (ExamAttempt $a) => [
            'label' => fa_date($a->started_at?->toDateString() ?? $a->created_at?->toDateString()),
            'value' => round((float) $a->percent, 1),
            'exam' => $a->exam?->title,
        ])->values();

        // ۲) دقت پاسخ‌گویی هر درس از پاسخ‌برگه‌ها
        $accuracyRows = AttemptAnswer::query()
            ->join('exam_attempts as at', 'at.id', '=', 'attempt_answers.attempt_id')
            ->join('questions as q', 'q.id', '=', 'attempt_answers.question_id')
            ->where('at.user_id', $target->id)
            ->where('at.status', 'finished')
            ->selectRaw('q.subject_id,
                SUM(CASE WHEN attempt_answers.is_correct = 1 THEN 1 ELSE 0 END) as correct,
                SUM(CASE WHEN attempt_answers.is_correct IS NOT NULL THEN 1 ELSE 0 END) as attempted')
            ->groupBy('q.subject_id')
            ->get();

        $subjectAccuracy = [];
        foreach ($accuracyRows as $row) {
            $root = $rootOf($row->subject_id);
            if (! $root) {
                continue;
            }
            $key = $root->id;
            if (! isset($subjectAccuracy[$key])) {
                $subjectAccuracy[$key] = ['title' => $root->title, 'correct' => 0, 'attempted' => 0];
            }
            $subjectAccuracy[$key]['correct'] += (int) $row->correct;
            $subjectAccuracy[$key]['attempted'] += (int) $row->attempted;
        }
        $subjectAccuracy = collect($subjectAccuracy)
            ->map(function ($row) {
                $row['accuracy'] = percent($row['correct'], $row['attempted']);

                return $row;
            })
            ->sortByDesc('attempted')
            ->take(8)
            ->values();

        // ۳) سطوح تسلط (ترکیب عملکرد آزمون + ماندگاری مرور)
        $mastery = $this->mastery->strengthsAndWeaknesses($target->id, 5);

        // ۴) دقیقه‌های مطالعه ۱۴ روز اخیر
        $from = now()->subDays(13)->toDateString();
        $sessions = StudySession::query()
            ->where('user_id', $target->id)
            ->where('date', '>=', $from)
            ->selectRaw('date, SUM(minutes) as minutes')
            ->groupBy('date')
            ->pluck('minutes', 'date');

        $study = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $study[] = [
                'label' => fa_date($d, 'm/d'),
                'value' => (int) ($sessions[$d] ?? 0),
            ];
        }
        $studyTotal = array_sum(array_column($study, 'value'));

        // ۵) ماندگاری مرور و صف عقب‌افتاده
        $retention = $this->reviews->retentionStats($target);
        $overdue = $this->reviews->pendingCount($target);

        // ۶) آمار کلی
        $totalAnswered = $accuracyRows->sum(fn ($r) => (int) $r->attempted);
        $totalCorrect = $accuracyRows->sum(fn ($r) => (int) $r->correct);
        $overallAccuracy = percent($totalCorrect, $totalAnswered);
        $avgPercent = $attempts->isEmpty() ? 0 : round((float) $attempts->avg('percent'), 1);

        $suggestions = $this->buildSuggestions(
            collect($mastery['weaknesses']),
            $overdue,
            $retention,
            $studyTotal,
            $avgPercent,
            $subjectAccuracy
        );

        return compact(
            'trend', 'subjectAccuracy', 'mastery', 'study', 'studyTotal',
            'retention', 'overdue', 'avgPercent', 'overallAccuracy', 'suggestions'
        ) + [
            'attemptsCount' => $attempts->count(),
            'bestPercent' => $attempts->isEmpty() ? 0 : round((float) $attempts->max('percent'), 1),
        ];
    }

    /** پیشنهادهای اصلاحی قانون‌محور به فارسی */
    private function buildSuggestions(Collection $weaknesses, int $overdue, array $retention, int $studyTotal, float $avgPercent, Collection $subjectAccuracy): array
    {
        $suggestions = [];

        foreach ($weaknesses->take(3) as $row) {
            if ($row->level < 50) {
                $suggestions[] = 'در درس «' . $row->subject?->title . '» تعداد بیشتری تست بزن و اشتباهاتت را تحلیل کن (تسلط فعلی: ' . fa_num(round($row->level)) . '٪).';
            }
        }

        if ($overdue > 0) {
            $suggestions[] = 'مرورهای عقب‌افتاده: ' . fa_num($overdue) . ' مورد — امروز همه را مرور کن تا چرخه یادگیری نشکند.';
        }

        if (($retention['retention'] ?? 0) < 60 && ($retention['total'] ?? 0) > 0) {
            $suggestions[] = 'ماندگاری مرور شما ' . fa_num($retention['retention']) . '٪ است؛ به‌جای حفظ طوطی‌وار، مفهوم را بازسازی کن و «سخت»ها را جدی بگیر.';
        }

        $dailyAvg = round($studyTotal / 14);
        if ($dailyAvg < 60) {
            $suggestions[] = 'میانگین مطالعه روزانه شما ' . fa_num($dailyAvg) . ' دقیقه است؛ برای شروع هدف حداقل ' . fa_num(90) . ' دقیقه در روز بگذار و با پومودورو پیش برو.';
        }

        if ($avgPercent > 0 && $avgPercent < 60) {
            $suggestions[] = 'میانگین درصد آزمون‌ها ' . fa_num($avgPercent) . '٪ است؛ آزمون‌های مبحثی کوتاه‌تر بده و قبل از آزمون، نقاط ضعف را مرور کن.';
        }

        $weakestSubject = $subjectAccuracy->sortBy('accuracy')->first();
        if ($weakestSubject && $weakestSubject['attempted'] >= 5 && $weakestSubject['accuracy'] < 50) {
            $suggestions[] = 'ضعف‌ترین درس شما بر اساس پاسخ‌برگه‌ها «' . $weakestSubject['title'] . '» با دقت ' . fa_num($weakestSubject['accuracy']) . '٪ است؛ ابتدا جزوه و خلاصه‌های آن درس را ببین و سپس آزمون مبحثی بده.';
        }

        if (empty($suggestions)) {
            $suggestions[] = 'عملکرد شما متعادل است؛ برای حفظ روند، برنامه هفتگی خود را ادامه بده و هر هفته یک آزمون جامع بده.';
        }

        return $suggestions;
    }
}
