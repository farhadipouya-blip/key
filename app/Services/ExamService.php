<?php

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * سرویس موتور آزمون: ساخت، شروع، ذخیره پاسخ، تصحیح و کارنامه
 */
class ExamService
{
    /**
     * تصحیح آزمون و محاسبه کارنامه
     * قوانین: نمره مثبت برای صحیح، نمره منفی نهایی برای غلط (اگر فعال)، نزده صفر
     */
    public function grade(ExamAttempt $attempt): ExamAttempt
    {
        if ($attempt->status === 'finished') {
            return $attempt;
        }

        $exam = $attempt->exam;
        $answers = $attempt->answers()->get()->keyBy('question_id');
        $examQuestions = $exam->examQuestions()->with('question.options')->get();

        $correct = 0;
        $wrong = 0;
        $blank = 0;
        $score = 0.0;
        $total = 0.0;

        foreach ($examQuestions as $eq) {
            $total += (float) $eq->marks;
            $answer = $answers->get($eq->question_id);
            $isDescriptive = $eq->question->type === 'descriptive';

            // سؤال تشریحی با پاسخ متنی: در انتظار تصحیح مدرس است و در نمره خودکار لحاظ نمی‌شود
            if ($isDescriptive) {
                if ($answer && ($answer->text_answer || $answer->selected_option_id)) {
                    // پاسخ داده ولی هنوز تصحیح نشده
                    $answer->forceFill(['is_correct' => null, 'score' => 0, 'marks' => $eq->marks])->save();
                } else {
                    $blank++;
                }
                continue;
            }

            if (! $answer || $answer->selected_option_id === null) {
                $blank++;
                if ($answer) {
                    $answer->forceFill(['is_correct' => null, 'score' => 0, 'marks' => $eq->marks])->save();
                }
                continue;
            }

            $option = $answer->selectedOption;
            if ($option && $option->is_correct) {
                $correct++;
                $score += (float) $eq->marks;
                $answer->forceFill(['is_correct' => true, 'score' => $eq->marks, 'marks' => $eq->marks])->save();
            } else {
                $wrong++;
                $penalty = $exam->negative_marking ? (float) ($exam->negative_value ?? 0.25) * (float) $eq->marks : 0;
                $score -= $penalty;
                $answer->forceFill(['is_correct' => false, 'score' => -1 * $penalty, 'marks' => $eq->marks])->save();
            }
        }

        // سؤالات تشریحی منتظر تصحیح مدرس می‌مانند؛ فقط تستی نمره می‌گیرند
        $percent = $total > 0 ? round(max(0, $score) / $total * 100, 2) : 0;

        $attempt->forceFill([
            'finished_at' => now(),
            'status' => 'finished',
            'score' => round(max(0, $score), 2),
            'total_score' => $total,
            'percent' => $percent,
            'correct_count' => $correct,
            'wrong_count' => $wrong,
            'blank_count' => $blank,
            'time_spent_seconds' => (int) $attempt->started_at->diffInSeconds(now()),
        ])->save();

        // افزودن سؤالات آزمون به صف مرور هوشمند (سؤالات اشتباه و نزده)
        app(ReviewService::class)->syncFromExam($attempt);

        // به‌روزرسانی سطح تسلط
        app(MasteryService::class)->updateFromAttempt($attempt);

        return $attempt;
    }

    /** ساخت آزمون خودکار از موضوعات با تعداد و سختی مشخص (سؤال تکراری اضافه نمی‌شود) */
    public function buildFromSubjects(Exam $exam, array $subjectIds, int $count, ?string $difficulty = null): int
    {
        $alreadyAttached = $exam->examQuestions()->pluck('question_id')->all();

        $query = Question::query()
            ->whereIn('subject_id', $subjectIds)
            ->where('is_active', true)
            ->when($alreadyAttached, fn ($q) => $q->whereNotIn('id', $alreadyAttached))
            ->inRandomOrder();
        if ($difficulty) {
            $query->where('difficulty', $difficulty);
        }
        $questions = $query->limit($count)->get();

        $order = (int) $exam->examQuestions()->max('order');
        foreach ($questions as $q) {
            $exam->examQuestions()->create([
                'question_id' => $q->id,
                'order' => ++$order,
                'marks' => $q->default_marks,
            ]);
        }

        $exam->forceFill([
            'total_marks' => (float) $exam->examQuestions()->sum('marks'),
        ])->save();

        return $questions->count();
    }

    /** تحلیل گزینه‌ها: درصد انتخاب هر گزینه در همه کارنامه‌های این آزمون */
    public function optionAnalysis(Exam $exam): Collection
    {
        return DB::table('attempt_answers as aa')
            ->join('exam_questions as eq', 'eq.question_id', '=', 'aa.question_id')
            ->join('exam_attempts as at', 'at.id', '=', 'aa.attempt_id')
            ->where('eq.exam_id', $exam->id)
            ->where('at.status', 'finished')
            ->selectRaw('aa.question_id, aa.selected_option_id, COUNT(*) as picked')
            ->groupBy('aa.question_id', 'aa.selected_option_id')
            ->get();
    }
}
