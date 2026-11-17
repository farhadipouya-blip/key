<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\Subject;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * آزمون‌ساز: ساخت آزمون، انتخاب سؤال از بانک، تولید خودکار، بارم‌دهی، انتشار
 */
class AdminExamController extends Controller
{
    public function index(Request $request)
    {
        $exams = Exam::query()
            ->with(['subject:id,title', 'creator:id,name'])
            ->withCount(['questions', 'attempts'])
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%' . $request->q . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'all' => Exam::count(),
            'published' => Exam::where('status', 'published')->count(),
            'draft' => Exam::where('status', 'draft')->count(),
            'attempts' => DB::table('exam_attempts')->where('status', 'finished')->count(),
        ];

        return view('admin.content.exams.index', compact('exams', 'stats'));
    }

    public function create()
    {
        return view('admin.content.exams.form', [
            'exam' => new Exam(['type' => 'timed', 'status' => 'draft', 'negative_value' => 0.25, 'pass_percent' => 50]),
            'subjects' => $this->rootSubjects(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;
        $data['status'] = 'draft'; // انتشار فقط از سازنده آزمون پس از افزودن سؤال
        $this->normalizeFlags($request, $data);

        $exam = Exam::create($data);

        AuditLog::record($request->user(), 'exam.created', $exam, ['title' => $exam->title]);

        return redirect()->route('admin.exams.builder', $exam)
            ->with('success', 'آزمون «' . $exam->title . '» ساخته شد. اکنون سؤالات را اضافه کنید.');
    }

    public function edit(Exam $exam)
    {
        return view('admin.content.exams.form', [
            'exam' => $exam,
            'subjects' => $this->rootSubjects(),
        ]);
    }

    public function update(Request $request, Exam $exam)
    {
        $data = $this->validated($request);
        $this->normalizeFlags($request, $data);

        $exam->update($data);

        AuditLog::record($request->user(), 'exam.updated', $exam, ['title' => $exam->title]);

        return redirect()->route('admin.exams.edit', $exam)
            ->with('success', 'مشخصات آزمون به‌روزرسانی شد.');
    }

    public function destroy(Request $request, Exam $exam)
    {
        $title = $exam->title;
        $exam->delete();

        AuditLog::record($request->user(), 'exam.deleted', null, ['title' => $title, 'id' => $exam->id]);

        return redirect()->route('admin.exams.index')
            ->with('success', 'آزمون «' . $title . '» حذف شد.');
    }

    // ------------------------------------------------------------------
    // سازنده آزمون
    // ------------------------------------------------------------------

    public function builder(Request $request, Exam $exam)
    {
        $exam->load(['examQuestions.question.subject:id,parent_id,title,level', 'examQuestions.question.subject.parent:id,title']);

        // بانک سؤال برای انتخاب
        $bank = Question::query()
            ->where('is_active', true)
            ->with(['subject:id,parent_id,title,level', 'subject.parent:id,title', 'options:id,question_id,order,is_correct'])
            ->when($request->filled('subject_id'), fn ($q) => $q->whereIn('subject_id', $this->subjectTreeIds((int) $request->subject_id)))
            ->when($request->filled('difficulty'), fn ($q) => $q->where('difficulty', $request->difficulty))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('q'), fn ($q) => $q->where('text', 'like', '%' . $request->q . '%'))
            ->whereNotIn('id', $exam->examQuestions()->pluck('question_id'))
            ->latest('id')
            ->limit(50)
            ->get();

        return view('admin.content.exams.builder', [
            'exam' => $exam,
            'bank' => $bank,
            'subjects' => $this->rootSubjects(),
        ]);
    }

    /** افزودن سؤال‌های انتخابی از بانک به آزمون */
    public function attachQuestions(Request $request, Exam $exam)
    {
        $data = $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'exists:questions,id'],
        ], [
            'question_ids.required' => 'حداقل یک سؤال را انتخاب کنید.',
        ]);

        $existing = $exam->examQuestions()->pluck('question_id')->all();
        $newIds = collect($data['question_ids'])->diff($existing)->values();
        $order = $exam->examQuestions()->max('order') ?: 0;

        foreach ($newIds as $qid) {
            $question = Question::find($qid);
            if (! $question || $question->type === 'descriptive' && $exam->type === 'simulator') {
                continue; // شبیه‌ساز فقط تستی
            }
            $exam->examQuestions()->create([
                'question_id' => $qid,
                'order' => ++$order,
                'marks' => $question->default_marks,
            ]);
        }

        $this->recalc($exam);
        AuditLog::record($request->user(), 'exam.questions_attached', $exam, ['added' => $newIds->count()]);

        return redirect()->route('admin.exams.builder', $exam)
            ->with('success', fa_num($newIds->count()) . ' سؤال به آزمون اضافه شد.');
    }

    /** تولید خودکار سؤال بر اساس درس‌ها و سختی (از بانک فعال) */
    public function autoGenerate(Request $request, Exam $exam)
    {
        $data = $request->validate([
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'count' => ['required', 'integer', 'min:1', 'max:100'],
            'difficulty' => ['nullable', Rule::in(['easy', 'medium', 'hard'])],
        ], [
            'subject_ids.required' => 'حداقل یک درس/مبحث انتخاب کنید.',
            'count.required' => 'تعداد سؤال الزامی است.',
        ]);

        // همه زیرمجموعه‌های انتخابی به‌صورت خودکار پوشش داده می‌شوند
        $ids = collect($data['subject_ids'])->flatMap(fn ($id) => $this->subjectTreeIds($id))->unique()->values()->all();

        $added = app(ExamService::class)->buildFromSubjects($exam, $ids, (int) $data['count'], $data['difficulty'] ?? null);

        AuditLog::record($request->user(), 'exam.auto_generated', $exam, ['added' => $added]);

        return redirect()->route('admin.exams.builder', $exam)
            ->with('success', fa_num($added) . ' سؤال به‌صورت خودکار از بانک اضافه شد.');
    }

    /** ویرایش بارم یا جابه‌جایی ترتیب یک سؤال */
    public function updateQuestion(Request $request, Exam $exam, ExamQuestion $examQuestion)
    {
        if ($examQuestion->exam_id !== $exam->id) {
            abort(404);
        }

        if ($request->filled('move')) {
            $dir = $request->input('move') === 'up' ? -1 : 1;
            $swap = ExamQuestion::where('exam_id', $exam->id)
                ->where('order', $examQuestion->order + $dir)
                ->first();
            if ($swap) {
                $current = $examQuestion->order;
                $examQuestion->update(['order' => $swap->order]);
                $swap->update(['order' => $current]);
            }
            return back();
        }

        $data = $request->validate([
            'marks' => ['required', 'numeric', 'min:0.25', 'max:100'],
        ], ['marks.min' => 'حداقل بارم ۰٫۲۵ است.']);

        $examQuestion->update(['marks' => $data['marks']]);
        $this->recalc($exam);

        return back()->with('success', 'بارم سؤال به‌روزرسانی شد.');
    }

    public function detachQuestion(Request $request, Exam $exam, ExamQuestion $examQuestion)
    {
        if ($examQuestion->exam_id !== $exam->id) {
            abort(404);
        }

        $examQuestion->delete();

        // بازشماری ترتیب بدون شکاف
        $order = 1;
        foreach ($exam->examQuestions()->orderBy('order')->get() as $eq) {
            $eq->update(['order' => $order++]);
        }

        $this->recalc($exam);

        return back()->with('success', 'سؤال از آزمون حذف شد.');
    }

    /** تغییر وضعیت: انتشار (نیازمند حداقل یک سؤال)، بستن، بازگشت به پیش‌نویس */
    public function setStatus(Request $request, Exam $exam)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['draft', 'published', 'closed'])],
        ]);

        if ($data['status'] === 'published' && $exam->examQuestions()->count() === 0) {
            return back()->with('error', 'برای انتشار آزمون، حداقل یک سؤال لازم است.');
        }

        $exam->update(['status' => $data['status']]);

        AuditLog::record($request->user(), 'exam.status_changed', $exam, ['status' => $data['status']]);

        $labels = ['draft' => 'پیش‌نویس', 'published' => 'منتشر شد', 'closed' => 'بسته شد'];
        return back()->with('success', 'وضعیت آزمون: ' . $labels[$data['status']]);
    }

    // ------------------------------------------------------------------

    /** محاسبه مجدد مجموع بارم */
    private function recalc(Exam $exam): void
    {
        $exam->forceFill(['total_marks' => (float) $exam->examQuestions()->sum('marks')])->save();
    }

    /** درخت کامل شناسه‌های موضوع (خود + فرزندان + نوه‌ها) */
    private function subjectTreeIds(int $subjectId): array
    {
        $childIds = Subject::where('parent_id', $subjectId)->pluck('id')->all();
        $grandIds = $childIds ? Subject::whereIn('parent_id', $childIds)->pluck('id')->all() : [];

        return array_merge([$subjectId], $childIds, $grandIds);
    }

    private function rootSubjects(): array
    {
        return Subject::whereNull('parent_id')->orderBy('order')->pluck('title', 'id')->all();
    }

    private function normalizeFlags(Request $request, array &$data): void
    {
        $data['negative_marking'] = $request->boolean('negative_marking');
        $data['shuffle_questions'] = $request->boolean('shuffle_questions');
        $data['shuffle_options'] = $request->boolean('shuffle_options');
        $data['is_public'] = $request->boolean('is_public');
        if (empty($data['negative_value'])) {
            $data['negative_value'] = null;
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', Rule::in(['practice', 'timed', 'simulator', 'homework'])],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'negative_value' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'pass_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'title.required' => 'عنوان آزمون الزامی است.',
            'type.required' => 'نوع آزمون الزامی است.',
            'duration_minutes.max' => 'مدت آزمون حداکثر ۶۰۰ دقیقه است.',
            'ends_at.after_or_equal' => 'پایان آزمون باید بعد از شروع آن باشد.',
            'pass_percent.max' => 'درصد قبلی بین ۰ تا ۱۰۰ است.',
        ]);
    }
}
