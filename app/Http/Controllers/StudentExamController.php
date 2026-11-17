<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Services\ExamService;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * تجربه دانشجویی آزمون: فهرست آزمون‌ها، برگزاری آنلاین، کارنامه
 */
class StudentExamController extends Controller
{
    /** مرکز آزمون: آزمون‌های منتشرشده + آمار من */
    public function hub(Request $request)
    {
        $user = $request->user();

        $exams = Exam::query()
            ->where('status', 'published')
            ->where(function ($q) {
                $q->where('is_public', true)
                    ->orWhere('created_by', auth()->id());
            })
            ->withCount('questions')
            ->with(['subject'])
            ->latest('id')
            ->get();

        $myAttempts = $user->attempts()->with('exam')->latest('id')->take(10)->get();

        $stats = [
            'total' => $user->attempts()->where('status', 'finished')->count(),
            'avg' => round((float) ($user->attempts()->where('status', 'finished')->avg('percent') ?? 0), 1),
            'best' => round((float) ($user->attempts()->where('status', 'finished')->max('percent') ?? 0), 1),
        ];

        return view('exams.hub', compact('exams', 'myAttempts', 'stats'));
    }

    public function show(Exam $exam)
    {
        $exam->loadCount('questions');
        $myAttempts = auth()->user()->attempts()->where('exam_id', $exam->id)->orderByDesc('id')->get();
        $canEnter = $exam->isOpenNow();

        return view('exams.show', compact('exam', 'myAttempts', 'canEnter'));
    }

    public function start(Exam $exam)
    {
        $user = auth()->user();

        if (! $exam->isOpenNow()) {
            return back()->with('error', 'این آزمون در حال حاضر در دسترس نیست.');
        }

        // یک تلاش هم‌زمان برای هر آزمون
        $active = $user->attempts()->where('exam_id', $exam->id)->where('status', 'in_progress')->first();
        if ($active) {
            return redirect()->route('exams.take', $active);
        }

        $number = $user->attempts()->where('exam_id', $exam->id)->count() + 1;

        $attempt = $user->attempts()->create([
            'exam_id' => $exam->id,
            'attempt_number' => $number,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        \App\Models\AuditLog::record($user, 'exam.started', $exam, ['attempt' => $attempt->id]);

        return redirect()->route('exams.take', $attempt);
    }

    /** صفحه برگزاری آزمون با ذخیره خودکار */
    public function take(ExamAttempt $attempt)
    {
        $user = auth()->user();
        if ($attempt->user_id !== $user->id) {
            abort(403);
        }

        if ($attempt->status === 'finished') {
            return redirect()->route('exams.result', $attempt);
        }

        if ($attempt->isExpired()) {
            app(ExamService::class)->grade($attempt);
            return redirect()->route('exams.result', $attempt)->with('info', 'زمان آزمون به پایان رسید و کارنامه صادر شد.');
        }

        $exam = $attempt->exam;
        $examQuestions = $exam->examQuestions()->with(['question.options', 'question.subject'])->get();
        $answers = $attempt->answers()->get()->keyBy('question_id');

        // نمایش تصادفی پایدار برای این تلاش
        if ($exam->shuffle_questions) {
            $examQuestions = $examQuestions->shuffle($attempt->id);
        }

        $remaining = $attempt->remainingSeconds();

        return view('exams.take', compact('attempt', 'exam', 'examQuestions', 'answers', 'remaining'));
    }

    /** ذخیره خودکار پاسخ (AJAX) */
    public function answer(Request $request, ExamAttempt $attempt, Question $question)
    {
        $user = auth()->user();
        if ($attempt->user_id !== $user->id || $attempt->status !== 'in_progress') {
            abort(403);
        }

        $data = $request->validate([
            'option_id' => ['nullable', 'integer', 'exists:question_options,id'],
            'text_answer' => ['nullable', 'string', 'max:10000'],
            'flagged' => ['nullable', 'boolean'],
        ]);

        if ($attempt->isExpired()) {
            return response()->json(['status' => 'expired'], 419);
        }

        // اعتبارسنجی تعلق گزینه به سؤال
        $optionId = $data['option_id'] ?? null;
        if ($optionId && ! $question->options()->whereKey($optionId)->exists()) {
            return response()->json(['message' => 'گزینه نامعتبر است'], 422);
        }

        $marks = (float) ($attempt->exam->examQuestions()->where('question_id', $question->id)->value('marks') ?: 1);

        AttemptAnswer::updateOrCreate(
            ['attempt_id' => $attempt->id, 'question_id' => $question->id],
            [
                'selected_option_id' => $question->type === 'multi' ? $optionId : null,
                'text_answer' => $question->type === 'descriptive' ? ($data['text_answer'] ?? null) : null,
                'flagged' => (bool) ($data['flagged'] ?? false),
                'answered_at' => now(),
                'marks' => $marks,
            ]
        );

        $answered = $attempt->answers()->whereNotNull('selected_option_id')->count();
        $total = $attempt->exam->examQuestions()->count();

        return response()->json(['status' => 'ok', 'answered' => $answered, 'total' => $total]);
    }

    public function finish(ExamAttempt $attempt)
    {
        $user = auth()->user();
        if ($attempt->user_id !== $user->id || $attempt->status !== 'in_progress') {
            abort(403);
        }

        $attempt = app(ExamService::class)->grade($attempt);
        \App\Models\AuditLog::record($user, 'exam.finished', $attempt->exam, ['percent' => $attempt->percent]);

        return redirect()->route('exams.result', $attempt)->with('success', 'آزمون با موفقیت ثبت شد.');
    }

    /** کارنامه تحلیلی */
    public function result(ExamAttempt $attempt)
    {
        $user = auth()->user();
        if ($attempt->user_id !== $user->id && ! $user->isStaff()) {
            abort(403);
        }

        $attempt->load(['exam', 'answers.question.options', 'answers.question.subject']);
        $examQuestions = $attempt->exam->examQuestions()->with(['question.options'])->get();
        $answers = $attempt->answers->keyBy('question_id');

        // تحلیل گزینه‌ها در کل آزمون
        $optionStats = app(ExamService::class)->optionAnalysis($attempt->exam);

        // تحلیل بر اساس مبحث
        $bySubject = [];
        foreach ($examQuestions as $eq) {
            $sid = $eq->question->subject_id;
            if (! isset($bySubject[$sid])) {
                $bySubject[$sid] = ['title' => $eq->question->subject->title, 'total' => 0, 'correct' => 0, 'wrong' => 0, 'blank' => 0];
            }
            $bySubject[$sid]['total']++;
            $answer = $answers->get($eq->question_id);
            if (! $answer || $answer->is_correct === null) {
                $bySubject[$sid]['blank']++;
            } elseif ($answer->is_correct) {
                $bySubject[$sid]['correct']++;
            } else {
                $bySubject[$sid]['wrong']++;
            }
        }

        $rankInExam = null;
        $participants = ExamAttempt::query()
            ->where('exam_id', $attempt->exam_id)
            ->where('status', 'finished')
            ->orderByDesc('percent')->pluck('user_id')->values();
        $pos = $participants->search($attempt->user_id);
        $rankInExam = $pos !== false ? $pos + 1 : null;
        $participantsCount = $participants->count();

        return view('exams.result', compact('attempt', 'examQuestions', 'answers', 'optionStats', 'bySubject', 'rankInExam', 'participantsCount'));
    }

    public function history(Request $request)
    {
        $user = $request->user();
        $attempts = $user->attempts()->with('exam')->orderByDesc('id')->paginate(15);

        return view('exams.history', compact('attempts'));
    }

    /** بانک تست برای تمرین آزاد با فیلتر مبحث و سختی */
    public function practice(Request $request)
    {
        $subjects = \App\Models\Subject::query()->whereNull('parent_id')->with('children')->get();

        $query = Question::query()->where('is_active', true)->with(['subject', 'options']);

        if ($request->filled('subject_id')) {
            $ids = [$request->subject_id];
            $subject = \App\Models\Subject::find($request->subject_id);
            if ($subject) {
                $ids = array_merge($ids, $subject->children->pluck('id')->all());
            }
            $query->whereIn('subject_id', $ids);
        }
        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $questions = $query->latest('id')->paginate(10)->withQueryString();

        return view('exams.practice', compact('questions', 'subjects'));
    }
}
