<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * بانک سؤال: ثبت/ویرایش سؤال تستی و تشریحی با گزینه‌ها و پشتیبانی فرمول LaTeX
 */
class AdminQuestionController extends Controller
{
    public function index(Request $request)
    {
        $questions = Question::query()
            ->with(['subject:id,parent_id,title,level', 'subject.parent:id,title'])
            ->when($request->filled('q'), fn ($q) => $q->where('text', 'like', '%' . $request->q . '%'))
            ->when($request->filled('subject_id'), fn ($q) => $this->scopeSubject($q, (int) $request->subject_id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('difficulty'), fn ($q) => $q->where('difficulty', $request->difficulty))
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->active === '1'))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'all' => Question::count(),
            'multi' => Question::where('type', 'multi')->count(),
            'descriptive' => Question::where('type', 'descriptive')->count(),
            'inactive' => Question::where('is_active', false)->count(),
        ];

        return view('admin.content.questions.index', [
            'questions' => $questions,
            'stats' => $stats,
            'subjects' => $this->subjectOptions(),
        ]);
    }

    public function create(Request $request)
    {
        $question = new Question([
            'type' => 'multi',
            'difficulty' => 'medium',
            'default_marks' => 1,
            'is_active' => true,
            'subject_id' => $request->input('subject_id'),
        ]);

        return view('admin.content.questions.form', [
            'question' => $question,
            'subjects' => $this->subjectOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;
        $data['is_active'] = $request->boolean('is_active');

        $question = DB::transaction(function () use ($request, $data) {
            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('uploads/questions', 'public');
            }
            $question = Question::create($data);
            $this->syncOptions($question, $data);
            return $question;
        });

        AuditLog::record($request->user(), 'question.created', $question, ['id' => $question->id]);

        return redirect()->route('admin.questions.edit', $question)
            ->with('success', 'سؤال با موفقیت ثبت شد.');
    }

    public function edit(Question $question)
    {
        $question->load('options');

        return view('admin.content.questions.form', [
            'question' => $question,
            'subjects' => $this->subjectOptions(),
        ]);
    }

    public function update(Request $request, Question $question)
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');

        DB::transaction(function () use ($request, $question, $data) {
            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('uploads/questions', 'public');
            }
            $question->update($data);
            $this->syncOptions($question, $data);
        });

        AuditLog::record($request->user(), 'question.updated', $question, ['id' => $question->id]);

        return redirect()->route('admin.questions.edit', $question)
            ->with('success', 'سؤال به‌روزرسانی شد.');
    }

    public function toggle(Request $request, Question $question)
    {
        $question->update(['is_active' => ! $question->is_active]);

        AuditLog::record($request->user(), 'question.toggled', $question, ['active' => $question->is_active]);

        return back()->with('success', $question->is_active ? 'سؤال فعال شد.' : 'سؤال غیرفعال شد.');
    }

    public function destroy(Request $request, Question $question)
    {
        $question->delete();

        AuditLog::record($request->user(), 'question.deleted', null, ['id' => $question->id]);

        return redirect()->route('admin.questions.index')
            ->with('success', 'سؤال حذف شد.');
    }

    /** افزودن/به‌روزرسانی گزینه‌ها از ورودی‌های option_texts و correct_option */
    private function syncOptions(Question $question, array $data): void
    {
        if ($data['type'] !== 'multi') {
            $question->options()->delete();
            return;
        }

        $texts = $data['option_texts'];
        $correctOrder = (int) $data['correct_option'];

        // حذف گزینه‌های اضافه‌شده قبلی (مثلاً بعد از کاهش تعداد گزینه)
        $question->options()->whereNotIn('order', array_keys($texts))->delete();

        foreach ($texts as $order => $text) {
            QuestionOption::updateOrCreate(
                ['question_id' => $question->id, 'order' => (int) $order],
                ['text' => trim($text), 'is_correct' => (int) $order === $correctOrder]
            );
        }
    }

    /** فیلتر موضوع با درنظرگرفتن زیرمجموعه‌ها */
    private function scopeSubject($query, int $subjectId)
    {
        $ids = Subject::query()
            ->where('id', $subjectId)
            ->orWhere('parent_id', $subjectId)
            ->orWhereIn('parent_id', Subject::where('parent_id', $subjectId)->select('id'))
            ->pluck('id');

        return $query->whereIn('subject_id', $ids);
    }

    private function subjectOptions(): array
    {
        return Subject::query()
            ->orderBy('order')
            ->get()
            ->mapWithKeys(fn (Subject $s) => [$s->id => $s->fullTitle()])
            ->all();
    }

    private function validated(Request $request): array
    {
        $type = $request->input('type', 'multi');

        $rules = [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'type' => ['required', Rule::in(['multi', 'descriptive'])],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'text' => ['required', 'string', 'max:20000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'answer_text' => ['nullable', 'string', 'max:20000'],
            'explanation' => ['nullable', 'string', 'max:20000'],
            'source' => ['nullable', 'string', 'max:150'],
            'year' => ['nullable', 'integer', 'digits:4', 'min:1300', 'max:1500'],
            'default_marks' => ['required', 'numeric', 'min:0.25', 'max:100'],
        ];

        $messages = [
            'subject_id.required' => 'انتخاب درس/مبحث الزامی است.',
            'subject_id.exists' => 'درس انتخابی معتبر نیست.',
            'text.required' => 'متن سؤال الزامی است.',
            'image.image' => 'فایل پیوست باید تصویر باشد.',
            'image.mimes' => 'فرمت تصویر باید jpg، png یا webp باشد.',
            'image.max' => 'حجم تصویر حداکثر ۴ مگابایت است.',
            'default_marks.min' => 'حداقل بارم سؤال ۰٫۲۵ است.',
            'year.digits' => 'سال طرح باید ۴ رقم باشد.',
        ];

        if ($type === 'multi') {
            $rules['option_texts'] = ['required', 'array', 'size:4'];
            $rules['option_texts.*'] = ['required', 'string', 'max:2000'];
            $rules['correct_option'] = ['required', 'integer', Rule::in([1, 2, 3, 4])];
            $messages['option_texts.size'] = 'سؤال تستی باید چهار گزینه داشته باشد.';
            $messages['option_texts.*.required'] = 'متن همه گزینه‌ها الزامی است.';
            $messages['correct_option.required'] = 'تعیین گزینه صحیح الزامی است.';
        }

        return $request->validate($rules, $messages);
    }
}
