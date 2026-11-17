<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FieldSelection;
use App\Models\HistoricalResult;
use App\Models\MajorUniversity;
use App\Models\RankEstimate;
use App\Models\Subject;
use App\Models\University;
use App\Services\RankService;
use App\Support\Jalali;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * ماژول تخمین رتبه و انتخاب رشته
 * محاسبه با RankService، ذخیره واقعی در rank_estimates و field_selections
 */
class RankController extends Controller
{
    /** گروه‌های آزمون */
    public const GROUPS = [
        'experimental' => 'علوم تجربی',
        'math' => 'ریاضی و فیزیک',
        'humanities' => 'علوم انسانی',
        'arts' => 'هنر',
        'language' => 'زبان‌های خارجی',
    ];

    /** نوع دوره دانشگاه */
    public const TUITION_TYPES = [
        'daily' => 'روزانه',
        'night' => 'شبانه',
        'self' => 'خودگردان',
        'full' => 'پرداخت‌محور',
    ];

    public function __construct(private RankService $rankService) {}

    // ------------------------------------------------------------------
    // تخمین رتبه
    // ------------------------------------------------------------------

    /** فرم تخمین + تاریخچه تخمین‌ها + نتایج تاریخی */
    public function index(Request $request)
    {
        $user = $request->user();

        $subjects = Subject::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        $estimates = RankEstimate::query()
            ->where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        $historical = HistoricalResult::query()
            ->where('user_id', $user->id)
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->get();

        // نتیجه آخرین تخمین برای نمایش پنل نتیجه (بعد از محاسبه یا از تاریخچه)
        $result = null;
        if ($request->session()->has('estimate_id')) {
            $result = RankEstimate::query()
                ->where('user_id', $user->id)
                ->find($request->session()->pull('estimate_id'));
        }

        $latest = $estimates->first();

        return view('rank.index', compact(
            'subjects', 'estimates', 'historical', 'result', 'latest'
        ));
    }

    /** محاسبه و ذخیره تخمین رتبه */
    public function estimate(Request $request)
    {
        $user = $request->user();

        // پشتیبانی از ارقام فارسی در ورودی درصدها (قبل از اعتبارسنجی)
        $scores = $request->input('scores', []);
        if (is_array($scores)) {
            foreach ($scores as &$row) {
                if (is_array($row) && isset($row['percent']) && $row['percent'] !== null && $row['percent'] !== '') {
                    $row['percent'] = Jalali::toLatinDigits((string) $row['percent']);
                }
            }
            unset($row);
            $request->merge(['scores' => $scores]);
        }

        $data = $request->validate([
            'group' => ['required', 'in:' . implode(',', array_keys(self::GROUPS))],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scores.*.special' => ['nullable', 'in:0,1'],
        ], [
            'group.required' => 'انتخاب گروه آزمون الزامی است.',
            'group.in' => 'گروه آزمون نامعتبر است.',
            'scores.required' => 'درصدهای دروس را وارد کنید.',
            'scores.*.percent.numeric' => 'درصد هر درس باید عدد باشد.',
            'scores.*.percent.min' => 'درصد نمی‌تواند منفی باشد.',
            'scores.*.percent.max' => 'درصد نمی‌تواند بیشتر از ۱۰۰ باشد.',
        ]);

        // ساخت آرایه ورودی سرویس: [['title'=>..., 'percent'=>..., 'is_special'=>...]]
        $subjectIds = array_keys($data['scores']);
        $subjects = Subject::query()->whereIn('id', $subjectIds)->get()->keyBy('id');

        $input = [];
        foreach ($data['scores'] as $subjectId => $row) {
            $subject = $subjects->get($subjectId);
            if (! $subject || (($row['percent'] ?? '') === null || $row['percent'] === '')) {
                continue;
            }
            $input[] = [
                'title' => $subject->title,
                'percent' => (float) Jalali::toLatinDigits((string) $row['percent']),
                'is_special' => (bool) ($row['special'] ?? 0),
            ];
        }

        if (empty($input)) {
            return back()->with('error', 'حداقل درصد یک درس را وارد کنید.');
        }

        $result = $this->rankService->estimate($user, $input, $data['group']);

        $estimate = RankEstimate::create([
            'user_id' => $user->id,
            'input' => $input,
            'estimated_rank' => $result['rank'],
            'percentile' => $result['percentile'],
            'scenario' => $result['scenario'],
        ]);

        AuditLog::record($user, 'rank.estimate.saved', $estimate, [
            'rank' => $result['rank'],
            'percentile' => $result['percentile'],
            'group' => $data['group'],
            'calibrated' => $result['calibrated'],
        ]);

        return redirect()
            ->route('rank.index')
            ->with('estimate_id', $estimate->id)
            ->with('success', 'تخمین رتبه با موفقیت محاسبه و ذخیره شد.');
    }

    /** افزودن نتیجه تاریخی کارنامه (برای کالیبراسیون تخمین) */
    public function historicalStore(Request $request)
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:1380', 'max:1450'],
            'exam_name' => ['nullable', 'string', 'max:100'],
            'total_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rank' => ['nullable', 'integer', 'min:1', 'max:6000000'],
        ], [
            'year.required' => 'سال آزمون الزامی است.',
            'year.min' => 'سال نامعتبر است.',
            'year.max' => 'سال نامعتبر است.',
            'total_percent.max' => 'درصد نمی‌تواند بیشتر از ۱۰۰ باشد.',
        ]);

        if (($data['total_percent'] ?? null) === null && ($data['rank'] ?? null) === null) {
            return back()->with('error', 'حداقل یکی از موارد «درصد» یا «رتبه» را وارد کنید.');
        }

        $result = HistoricalResult::create([
            'user_id' => $request->user()->id,
            'year' => (int) $data['year'],
            'exam_name' => $data['exam_name'] ?? 'کنکور سراسری',
            'total_percent' => $data['total_percent'] ?? null,
            'rank' => $data['rank'] ?? null,
            'source' => 'manual',
        ]);

        AuditLog::record($request->user(), 'rank.historical.saved', $result, [
            'year' => $result->year,
            'rank' => $result->rank,
        ]);

        return back()->with('success', 'نتیجه تاریخی ذخیره شد؛ تخمین‌های بعدی با آن کالیبره می‌شوند.');
    }

    /** حذف نتیجه تاریخی (فقط مالک) */
    public function historicalDestroy(Request $request, HistoricalResult $result)
    {
        if ($result->user_id !== $request->user()->id) {
            abort(403, 'شما فقط نتایج خودتان را می‌توانید حذف کنید.');
        }

        $result->delete();

        return back()->with('success', 'نتیجه تاریخی حذف شد.');
    }

    // ------------------------------------------------------------------
    // بانک رشته‌ها / انتخاب رشته
    // ------------------------------------------------------------------

    /** بانک جستجوی رشته/دانشگاه با شانس قبولی */
    public function selection(Request $request)
    {
        $user = $request->user();

        $latestEstimate = RankEstimate::query()
            ->where('user_id', $user->id)
            ->whereNotNull('estimated_rank')
            ->latest()
            ->first();

        // رتبه مؤثر: رتبه تخمینی کاربر یا ورودی دستی در فیلتر
        $rank = $latestEstimate?->estimated_rank;
        if ($request->filled('rank')) {
            $rank = (int) Jalali::toLatinDigits((string) $request->input('rank'));
        }

        $year = (int) ($request->input('year', MajorUniversity::query()->max('year') ?: now()->format('Y')));
        $group = $request->input('group', $user->field_group ?: 'math');
        $tuition = $request->input('tuition_type');
        $q = trim((string) $request->input('q'));

        $query = MajorUniversity::query()
            ->where('year', $year)
            ->with(['major', 'university']);

        if (array_key_exists($group, self::GROUPS)) {
            $query->whereHas('major', fn ($m) => $m->where('group', $group));
        }

        if ($tuition && array_key_exists($tuition, self::TUITION_TYPES)) {
            $query->where('tuition_type', $tuition);
        }

        if ($q !== '') {
            $like = '%' . $q . '%';
            $query->where(function ($w) use ($like) {
                $w->whereHas('major', fn ($m) => $m->where('title', 'like', $like))
                    ->orWhereHas('university', fn ($u) => $u->where('name', 'like', $like)->orWhere('city', 'like', $like));
            });
        }

        // نزدیک‌ترین حدنصاب‌ها (شانس بالاتر) اول
        $rows = $query->orderByRaw('last_rank_min IS NULL, last_rank_min ASC')
            ->orderBy('last_rank_max')
            ->paginate(15)
            ->withQueryString();

        $rows->getCollection()->each(function (MajorUniversity $mu) use ($rank) {
            $mu->chance = $mu->chancePercent($rank);
        });

        $years = MajorUniversity::query()->distinct()->orderByDesc('year')->pluck('year');
        $universitiesCount = University::count();
        $selected = old('items', []);

        return view('rank.selection', compact(
            'rows', 'years', 'year', 'group', 'tuition', 'q', 'rank',
            'latestEstimate', 'universitiesCount', 'selected'
        ));
    }

    /** ذخیره فرم انتخاب رشته + مرتب‌سازی آیتم‌ها + ثبت نهایی */
    public function selectionSave(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'selection_id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string', 'max:200'],
            'items' => ['required_unless:action,move,status', 'array', 'max:100'],
            'items.*' => ['integer'],
            'action' => ['nullable', 'in:move,status'],
            'item_id' => ['nullable', 'integer'],
            'direction' => ['nullable', 'in:up,down'],
            'status' => ['nullable', 'in:draft,final'],
        ], [
            'items.required' => 'حداقل یک رشته را انتخاب کنید.',
            'items.max' => 'حداکثر ۱۰۰ کد رشته می‌توانید انتخاب کنید.',
            'title.max' => 'عنوان فرم انتخاب حداکثر ۲۰۰ کاراکتر است.',
        ]);

        // ویرایش فرم موجود (مرتب‌سازی یا ثبت نهایی) — بررسی مالکیت
        $selection = null;
        if (! empty($data['selection_id'])) {
            $selection = FieldSelection::query()->find($data['selection_id']);
            if (! $selection || $selection->user_id !== $user->id) {
                abort(403, 'شما فقط فرم‌های انتخاب خودتان را می‌توانید تغییر دهید.');
            }
        }

        if ($selection && ($data['action'] ?? null) === 'move') {
            $this->moveItem($selection, (int) ($data['item_id'] ?? 0), $data['direction'] ?? 'up');
            $selection->touch();

            return redirect()
                ->route('rank.selections.show', $selection)
                ->with('success', 'ترتیب کد رشته‌ها به‌روزرسانی شد.');
        }

        if ($selection && ($data['action'] ?? null) === 'status') {
            $selection->status = $data['status'] ?? 'draft';
            $selection->save();

            AuditLog::record($user, 'rank.selection.status', $selection, ['status' => $selection->status]);

            return redirect()
                ->route('rank.selections.show', $selection)
                ->with('success', $selection->status === 'final' ? 'فرم انتخاب رشته نهایی شد.' : 'فرم به حالت پیش‌نویس برگشت.');
        }

        // ساخت/به‌روزرسانی آیتم‌ها به‌ترتیب ارسالی
        $itemIds = array_values(array_unique(array_map('intval', $data['items'] ?? [])));
        $valid = MajorUniversity::query()->whereIn('id', $itemIds)->pluck('id');
        if ($valid->isEmpty()) {
            return back()->with('error', 'کد رشته‌های انتخابی معتبر نیستند.');
        }

        // فقط کدهای معتبر، به‌ترتیب انتخاب کاربر
        $ordered = collect($itemIds)->filter(fn ($id) => $valid->contains($id))->values();

        if ($selection) {
            $selection->items = $this->buildItems($ordered);
            $selection->analysis = $this->buildAnalysis($ordered);
            if (! empty($data['title'])) {
                $selection->title = $data['title'];
            }
            $selection->touch();
        } else {
            $selection = FieldSelection::create([
                'user_id' => $user->id,
                'title' => $data['title'] ?: ('فرم انتخاب رشته — ' . Jalali::format(now()->toDateString())),
                'items' => $this->buildItems($ordered),
                'analysis' => $this->buildAnalysis($ordered),
                'status' => 'draft',
            ]);
        }

        AuditLog::record($user, 'rank.selection.saved', $selection, [
            'items' => $ordered->count(),
            'selection_id' => $selection->id,
        ]);

        return redirect()
            ->route('rank.selections.show', $selection)
            ->with('success', 'فرم انتخاب رشته با ' . fa_num($ordered->count()) . ' کد رشته ذخیره شد.');
    }

    /** فهرست فرم‌های انتخاب ذخیره‌شده کاربر */
    public function selections(Request $request)
    {
        $selections = FieldSelection::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (FieldSelection $selection) {
                $summary = $this->summarize($selection);
                $selection->summary = $summary;

                return $selection;
            });

        return view('rank.selections', compact('selections'));
    }

    /** جزئیات فرم انتخاب + خلاصه شانس + گزارش نهایی قابل چاپ */
    public function selectionShow(Request $request, FieldSelection $selection)
    {
        if ($selection->user_id !== $request->user()->id) {
            abort(403, 'شما فقط فرم‌های انتخاب خودتان را می‌توانید مشاهده کنید.');
        }

        $latestEstimate = RankEstimate::query()
            ->where('user_id', $selection->user_id)
            ->whereNotNull('estimated_rank')
            ->latest()
            ->first();

        $items = $this->loadItems($selection, $latestEstimate?->estimated_rank);
        $summary = $this->summarize($selection);

        return view('rank.selection_show', compact('selection', 'items', 'summary', 'latestEstimate'));
    }

    // ------------------------------------------------------------------
    // متدهای کمکی
    // ------------------------------------------------------------------

    /** ساخت آرایه items با اولویت و شانس */
    private function buildItems(Collection $orderedIds): array
    {
        $rank = $this->currentRank();
        $rows = MajorUniversity::query()->with(['major', 'university'])->whereIn('id', $orderedIds)->get()->keyBy('id');

        return $orderedIds->map(function ($id, $index) use ($rows, $rank) {
            $mu = $rows->get($id);
            if (! $mu) {
                return null;
            }

            return [
                'major_university_id' => (int) $id,
                'priority' => $index + 1,
                'chance' => $mu->chancePercent($rank),
                'title' => $mu->major?->title . ' — ' . $mu->university?->name,
            ];
        })->filter()->values()->all();
    }

    /** خلاصه تحلیل برای ذخیره در ستون analysis */
    private function buildAnalysis(Collection $orderedIds): array
    {
        $rank = $this->currentRank();
        $rows = MajorUniversity::query()->whereIn('id', $orderedIds)->get();
        $chances = $rows->map(fn (MajorUniversity $mu) => $mu->chancePercent($rank));

        return [
            'estimated_rank' => $rank,
            'total' => $rows->count(),
            'avg_chance' => $chances->isEmpty() ? 0 : round($chances->avg(), 1),
            'safe_count' => $chances->filter(fn ($c) => $c >= 60)->count(),
            'mid_count' => $chances->filter(fn ($c) => $c >= 30 && $c < 60)->count(),
            'risky_count' => $chances->filter(fn ($c) => $c < 30)->count(),
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /** رتبه تخمینی فعلی کاربر جاری */
    private function currentRank(): ?int
    {
        $estimate = RankEstimate::query()
            ->where('user_id', auth()->id())
            ->whereNotNull('estimated_rank')
            ->latest()
            ->first();

        return $estimate?->estimated_rank;
    }

    /** جابه‌جایی آیتم (بالا/پایین) در فرم انتخاب */
    private function moveItem(FieldSelection $selection, int $itemId, string $direction): void
    {
        $items = collect($selection->items ?? [])
            ->sortBy('priority')->values();

        $index = $items->search(fn ($item) => (int) ($item['major_university_id'] ?? 0) === $itemId);
        if ($index === false) {
            return;
        }

        $target = $direction === 'up' ? $index - 1 : $index + 1;
        if ($target < 0 || $target >= $items->count()) {
            return;
        }

        $values = $items->values()->all();
        [$values[$index], $values[$target]] = [$values[$target], $values[$index]];

        $selection->items = collect($values)->map(function ($item, $i) {
            $item['priority'] = $i + 1;

            return $item;
        })->values()->all();

        $selection->save();
    }

    /** بارگذاری آیتم‌های فرم انتخاب با رکوردهای کامل و شانس به‌روز */
    private function loadItems(FieldSelection $selection, ?int $rank): Collection
    {
        $items = collect($selection->items ?? [])
            ->sortBy('priority')->values();

        $ids = $items->pluck('major_university_id')->filter()->all();
        $rows = MajorUniversity::query()
            ->with(['major', 'university'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return $items->map(function ($item) use ($rows, $rank) {
            $mu = $rows->get($item['major_university_id'] ?? null);
            $item['row'] = $mu;
            $item['chance'] = $mu ? $mu->chancePercent($rank) : 0;

            return $item;
        });
    }

    /** خلاصه شانس برای نمایش و گزارش */
    private function summarize(FieldSelection $selection): array
    {
        $analysis = $selection->analysis ?? [];
        $items = collect($selection->items ?? []);

        return [
            'total' => $items->count(),
            'avg_chance' => $analysis['avg_chance'] ?? 0,
            'safe_count' => $analysis['safe_count'] ?? 0,
            'mid_count' => $analysis['mid_count'] ?? 0,
            'risky_count' => $analysis['risky_count'] ?? 0,
            'estimated_rank' => $analysis['estimated_rank'] ?? null,
            'status' => $selection->status,
        ];
    }
}
