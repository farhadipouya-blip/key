<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * مدیریت درس و مبحث: درخت «درس > فصل > مبحث»
 * سطح هر گره به‌صورت خودکار از والد تعیین می‌شود:
 * بدون والد=درس (root) | والدِ درس=فصل (chapter) | والدِ فصل=مبحث (lesson)
 */
class AdminSubjectController extends Controller
{
    public function index(Request $request)
    {
        $editing = null;
        if ($request->filled('edit')) {
            $editing = Subject::findOrFail($request->input('edit'));
        }

        $roots = Subject::query()
            ->whereNull('parent_id')
            ->withCount('questions')
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        $stats = [
            'courses' => Subject::whereNull('parent_id')->count(),
            'chapters' => Subject::whereIn('parent_id', Subject::whereNull('parent_id')->select('id'))->count(),
            'lessons' => Subject::where('level', 'lesson')->count(),
            'questions' => Question::count(),
        ];

        return view('admin.content.subjects.index', [
            'roots' => $roots,
            'editing' => $editing,
            'stats' => $stats,
            'parents' => $this->parentOptions($editing),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $level = $this->resolveLevel($data['parent_id'] ?? null);
        $data['level'] = $level;
        $data['order'] = $data['order'] ?? (int) (Subject::where('parent_id', $data['parent_id'] ?? null)->max('order') + 1);
        $data['is_active'] = $request->boolean('is_active');

        $subject = Subject::create($data);

        AuditLog::record($request->user(), 'subject.created', $subject, ['title' => $subject->fullTitle()]);

        return redirect()->route('admin.subjects.index')
            ->with('success', '«' . $subject->title . '» ثبت شد.');
    }

    public function update(Request $request, Subject $subject)
    {
        $data = $this->validated($request, $subject);

        // تغییر والد: اگر والد معتبر و غیرخود باشد، سطح دوباره محاسبه می‌شود
        if (isset($data['parent_id']) && (int) $data['parent_id'] === $subject->id) {
            unset($data['parent_id']);
        }
        $data['level'] = $this->resolveLevel($data['parent_id'] ?? null);
        $data['is_active'] = $request->boolean('is_active');

        $subject->update($data);

        AuditLog::record($request->user(), 'subject.updated', $subject, ['title' => $subject->fullTitle()]);

        return redirect()->route('admin.subjects.index')
            ->with('success', '«' . $subject->title . '» به‌روزرسانی شد.');
    }

    public function destroy(Request $request, Subject $subject)
    {
        // سؤالات و زیرمجموعه‌ها با کلید خارجی cascade حذف می‌شوند
        $title = $subject->fullTitle();
        $questionsCount = $subject->questions()->count();

        $subject->delete();

        AuditLog::record($request->user(), 'subject.deleted', null, ['title' => $title, 'questions_removed' => $questionsCount]);

        return redirect()->route('admin.subjects.index')
            ->with('success', '«' . $title . '» و همه زیرمجموعه‌های آن حذف شد.');
    }

    /** سطح گره بر اساس والد */
    private function resolveLevel(?int $parentId): string
    {
        if (! $parentId) {
            return 'root';
        }
        $parent = Subject::find($parentId);
        if (! $parent) {
            return 'root';
        }
        return match ($parent->level) {
            'root' => 'chapter',
            'chapter' => 'lesson',
            default => 'lesson',
        };
    }

    /** گزینه‌های والد: فقط درس‌ها و فصل‌ها (مبحث والد نمی‌شود) */
    private function parentOptions(?Subject $editing): array
    {
        return Subject::query()
            ->whereIn('level', ['root', 'chapter'])
            ->when($editing, fn ($q) => $q->where('id', '!=', $editing->id))
            ->orderBy('order')
            ->get()
            ->mapWithKeys(function (Subject $s) {
                $type = $s->level === 'root' ? 'درس' : 'فصل';
                return [$s->id => $type . ': ' . $s->title];
            })
            ->all();
    }

    private function validated(Request $request, ?Subject $subject = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [
            'title.required' => 'عنوان الزامی است.',
            'title.max' => 'عنوان حداکثر ۱۲۰ نویسه است.',
            'parent_id.exists' => 'والد انتخابی معتبر نیست.',
            'order.integer' => 'ترتیب باید عدد باشد.',
        ]);

        // سلسله‌مراتب: مبحث نمی‌تواند والد شود
        if (! empty($data['parent_id'])) {
            $parent = Subject::find($data['parent_id']);
            if ($parent && $parent->level === 'lesson') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'parent_id' => 'امکان انتخاب مبحث به‌عنوان والد وجود ندارد.',
                ]);
            }
        }

        return $data;
    }
}
