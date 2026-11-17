<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Bookmark;
use App\Models\Summary;
use App\Models\SummaryNote;
use App\Models\Subject;
use Illuminate\Http\Request;

/**
 * بانک خلاصه و نکته: خلاصه‌های عمومی، خلاصه‌های شخصی، یادداشت، علامت‌گذاری و مرور
 */
class SummaryController extends Controller
{
    /** لیست خلاصه‌ها با تب‌ها: عمومی | شخصی من | علامت‌گذاری‌شده */
    public function index(Request $request)
    {
        $user = $request->user();
        $tab = $request->query('tab', 'public');
        $category = $request->query('category');
        $q = trim((string) $request->query('q'));
        $subjectId = $request->query('subject_id');

        $base = Summary::query()->with('subject');

        $applyFilters = function ($query) use ($category, $q, $subjectId) {
            if ($category) {
                $query->where('category', $category);
            }
            if ($subjectId) {
                $query->where('subject_id', $subjectId);
            }
            if ($q !== '') {
                $query->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhere('content', 'like', "%{$q}%");
                });
            }
            return $query;
        };

        // شناسه خلاصه‌های علامت‌گذاری‌شده کاربر (بدون نیاز به رابطه مدل)
        $bookmarkedIds = Bookmark::query()
            ->where('user_id', $user->id)
            ->where('bookmarkable_type', Summary::class)
            ->pluck('bookmarkable_id');

        $summariesQuery = match ($tab) {
            'mine' => $applyFilters($base->where('user_id', $user->id)),
            'bookmarked' => $applyFilters($base->whereIn('id', $bookmarkedIds)),
            default => $applyFilters($base->where('user_id', null)->where('is_public', true)),
        };

        $summaries = $summariesQuery
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $categories = ['خلاصه', 'نکته', 'فرمول', 'خط‌کشی'];
        $subjects = Subject::query()->whereNotNull('parent_id')->orderBy('title')->get(['id', 'title', 'parent_id']);

        $counts = [
            'public' => Summary::query()->where('user_id', null)->where('is_public', true)->count(),
            'mine' => Summary::query()->where('user_id', $user->id)->count(),
            'bookmarked' => Bookmark::query()->where('user_id', $user->id)
                ->where('bookmarkable_type', Summary::class)->count(),
        ];

        $canManage = $user->canAccess('summaries.manage');

        return view('summaries.index', compact(
            'summaries', 'tab', 'category', 'q', 'subjectId', 'categories', 'subjects', 'counts', 'canManage'
        ));
    }

    /** فرم ساخت خلاصه شخصی */
    public function create(Request $request)
    {
        $subjects = Subject::query()->whereNotNull('parent_id')->orderBy('title')->get(['id', 'title', 'parent_id']);
        $canPublish = $request->user()->canAccess('summaries.manage');

        return view('summaries.create', compact('subjects', 'canPublish'));
    }

    /** ذخیره خلاصه شخصی؛ دارندگان مجوز summaries.manage می‌توانند عمومی بسازند */
    public function store(Request $request)
    {
        $data = $this->validated($request, $request->user());

        $summary = Summary::create([
            'user_id' => $request->user()->id,
            'created_by' => $request->user()->id,
            'subject_id' => $data['subject_id'] ?? null,
            'title' => $data['title'],
            'content' => $data['content'],
            'category' => $data['category'] ?? null,
            'tags' => $data['tags'] ?? null,
            'is_public' => $data['is_public'] ?? false,
        ]);

        AuditLog::record($request->user(), 'summary.created', $summary, ['title' => $summary->title]);

        return redirect()->route('summaries.show', $summary)->with('success', 'خلاصه ذخیره شد.');
    }

    /** نمایش خلاصه + یادداشت‌های من + علامت‌گذاری + افزودن به مرور */
    public function show(Request $request, Summary $summary)
    {
        $user = $request->user();

        // دسترسی: خلاصه عمومی منتشرشده یا خلاصه شخصی خودم
        if (! ($summary->is_public || $summary->user_id === $user->id)) {
            abort(403, 'به این خلاصه دسترسی ندارید.');
        }

        $notes = SummaryNote::query()->where('summary_id', $summary->id)->where('user_id', $user->id)->get();
        $bookmarked = Bookmark::query()
            ->where('user_id', $user->id)
            ->where('bookmarkable_type', Summary::class)
            ->where('bookmarkable_id', $summary->id)
            ->exists();

        $inReview = \App\Models\Review::query()
            ->where('user_id', $user->id)
            ->where('reviewable_type', Summary::class)
            ->where('reviewable_id', $summary->id)
            ->exists();

        return view('summaries.show', compact('summary', 'notes', 'bookmarked', 'inReview'));
    }

    /** ویرایش خلاصه (فقط مالک یا summaries.manage) */
    public function edit(Request $request, Summary $summary)
    {
        $this->authorizeManage($request, $summary);

        $subjects = Subject::query()->whereNotNull('parent_id')->orderBy('title')->get(['id', 'title', 'parent_id']);
        $canPublish = $request->user()->canAccess('summaries.manage');

        return view('summaries.edit', compact('summary', 'subjects', 'canPublish'));
    }

    public function update(Request $request, Summary $summary)
    {
        $this->authorizeManage($request, $summary);

        $data = $this->validated($request, $request->user());
        $summary->update([
            'subject_id' => $data['subject_id'] ?? null,
            'title' => $data['title'],
            'content' => $data['content'],
            'category' => $data['category'] ?? null,
            'tags' => $data['tags'] ?? null,
            'is_public' => $data['is_public'] ?? false,
        ]);

        AuditLog::record($request->user(), 'summary.updated', $summary, ['title' => $summary->title]);

        return redirect()->route('summaries.show', $summary)->with('success', 'خلاصه به‌روزرسانی شد.');
    }

    /** حذف خلاصه (فقط مالک یا summaries.manage) */
    public function destroy(Request $request, Summary $summary)
    {
        $this->authorizeManage($request, $summary);

        AuditLog::record($request->user(), 'summary.deleted', $summary, ['title' => $summary->title]);
        $summary->delete();

        return redirect()->route('summaries.index')->with('success', 'خلاصه حذف شد.');
    }

    /** افزودن یادداشت شخصی روی خلاصه */
    public function storeNote(Request $request, Summary $summary)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ], ['body.required' => 'متن یادداشت الزامی است.']);

        SummaryNote::create([
            'summary_id' => $summary->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return back()->with('success', 'یادداشت ثبت شد.');
    }

    /** علامت‌گذاری/حذف علامت خلاصه (bookmarks morph) */
    public function bookmark(Request $request, Summary $summary)
    {
        $user = $request->user();

        $existing = Bookmark::query()
            ->where('user_id', $user->id)
            ->where('bookmarkable_type', Summary::class)
            ->where('bookmarkable_id', $summary->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('success', 'علامت‌گذاری حذف شد.');
        }

        Bookmark::create([
            'user_id' => $user->id,
            'bookmarkable_type' => Summary::class,
            'bookmarkable_id' => $summary->id,
        ]);

        return back()->with('success', 'خلاصه علامت‌گذاری شد.');
    }

    /** پنل مدیریت خلاصه‌های عمومی (summaries.manage) */
    public function adminIndex(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $summaries = Summary::query()->with('subject')
            ->where('user_id', null)
            ->when($q !== '', fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $categories = ['خلاصه', 'نکته', 'فرمول', 'خط‌کشی'];
        $subjects = Subject::query()->whereNotNull('parent_id')->orderBy('title')->get(['id', 'title', 'parent_id']);

        return view('summaries.admin_index', compact('summaries', 'q', 'categories', 'subjects'));
    }

    /** ساخت خلاصه عمومی توسط مدیر محتوا */
    public function adminStore(Request $request)
    {
        $data = $this->validated($request, $request->user(), forcePublic: true);

        $summary = Summary::create([
            'user_id' => null,
            'created_by' => $request->user()->id,
            'subject_id' => $data['subject_id'] ?? null,
            'title' => $data['title'],
            'content' => $data['content'],
            'category' => $data['category'] ?? null,
            'tags' => $data['tags'] ?? null,
            'is_public' => true,
        ]);

        AuditLog::record($request->user(), 'summary.public_created', $summary, ['title' => $summary->title]);

        return back()->with('success', 'خلاصه عمومی منتشر شد.');
    }

    public function adminDestroy(Request $request, Summary $summary)
    {
        AuditLog::record($request->user(), 'summary.public_deleted', $summary, ['title' => $summary->title]);
        $summary->delete();

        return back()->with('success', 'خلاصه عمومی حذف شد.');
    }

    // ------------------------------------------------------------------

    private function validated(Request $request, $user, bool $forcePublic = false): array
    {
        $canPublish = $forcePublic || $user->canAccess('summaries.manage');

        $rules = [
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string', 'max:20000'],
            'category' => ['nullable', 'in:خلاصه,نکته,فرمول,خط‌کشی'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'tags' => ['nullable', 'string', 'max:300'],
        ];

        if ($canPublish) {
            $rules['is_public'] = ['nullable', 'boolean'];
        }

        $data = $request->validate($rules, [
            'title.required' => 'عنوان خلاصه الزامی است.',
            'content.required' => 'متن خلاصه الزامی است.',
            'category.in' => 'دسته خلاصه نامعتبر است.',
        ]);

        // برچسب‌ها: رشته جدا با ویرگول به آرایه
        if (! empty($data['tags'])) {
            $data['tags'] = array_values(array_filter(array_map('trim', explode('،', str_replace(',', '،', $data['tags'])))));
        } else {
            $data['tags'] = null;
        }

        if ($canPublish && $request->boolean('is_public')) {
            $data['is_public'] = true;
        } else {
            unset($data['is_public']);
        }

        return $data;
    }

    /** فقط مالک یا دارندگان مجوز مدیریت */
    private function authorizeManage(Request $request, Summary $summary): void
    {
        $user = $request->user();
        if ($summary->user_id === $user->id) {
            return;
        }
        if ($user->canAccess('summaries.manage')) {
            return;
        }
        abort(403, 'فقط مالک خلاصه یا مدیر محتوا اجازه این عملیات را دارد.');
    }
}
