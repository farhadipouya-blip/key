<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\PlanItem;
use App\Models\StudyPlan;
use App\Models\StudySession;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * ماژول برنامه مطالعاتی: ساخت برنامه، آیتم‌ها، ثبت جلسه واقعی، هدف و گزارش استمرار
 */
class PlanningController extends Controller
{
    /** برنامه فعال + آیتم‌های امروز/هفته + ثبت جلسه + هدف + مقایسه برنامه با عملکرد */
    public function index(Request $request)
    {
        $user = $request->user();

        $plan = StudyPlan::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();

        $today = now()->toDateString();
        $weekEnd = now()->addDays(6)->toDateString();

        $todayItems = collect();
        $weekItems = collect();
        $weekPlanned = 0;
        $weekCompleted = 0;
        $weekSessionsMinutes = 0;

        if ($plan) {
            $items = PlanItem::query()
                ->where('plan_id', $plan->id)
                ->where('user_id', $user->id)
                ->with('subject')
                ->orderBy('date')
                ->orderBy('id')
                ->get();

            $todayItems = $items->where('date', $today)->values();
            $weekItems = $items->whereBetween('date', [$today, $weekEnd])->values();
            $weekPlanned = (int) $weekItems->sum('target_minutes');
            $weekCompleted = (int) $weekItems->sum('completed_minutes');
        }

        $weekSessionsMinutes = (int) StudySession::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$today, $weekEnd])
            ->sum('minutes');

        $goals = Goal::query()->where('user_id', $user->id)->orderByDesc('id')->take(6)->get();

        $subjects = Subject::query()->whereNotNull('parent_id')->orderBy('title')->get(['id', 'title', 'parent_id']);

        // آخرین جلسات ثبت‌شده
        $sessions = StudySession::query()->where('user_id', $user->id)->with('subject')->orderByDesc('id')->take(8)->get();

        return view('planning.index', compact(
            'plan', 'todayItems', 'weekItems', 'weekPlanned', 'weekCompleted',
            'weekSessionsMinutes', 'goals', 'subjects', 'sessions'
        ));
    }

    /** ساخت برنامه مطالعاتی جدید (عنوان، بازه، هدف هفتگی) */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'weekly_target_minutes' => ['required', 'integer', 'min:30', 'max:10080'],
        ], [
            'title.required' => 'عنوان برنامه الزامی است.',
            'start_date.required' => 'تاریخ شروع الزامی است.',
            'end_date.after_or_equal' => 'تاریخ پایان باید بعد یا برابر تاریخ شروع باشد.',
            'weekly_target_minutes.required' => 'هدف مطالعه هفتگی (دقیقه) الزامی است.',
        ]);

        $user = $request->user();

        $plan = StudyPlan::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => 'active',
            'generated_by' => 'manual',
            'weekly_target_minutes' => $data['weekly_target_minutes'],
        ]);

        \App\Models\AuditLog::record($user, 'plan.created', $plan, ['title' => $plan->title]);

        return redirect()->route('plan.index')->with('success', 'برنامه مطالعاتی ساخته شد. حالا آیتم‌های آن را اضافه کنید.');
    }

    /** افزودن آیتم به برنامه (تاریخ، مبحث، زمان هدف) */
    public function storeItem(Request $request, StudyPlan $plan)
    {
        if ($plan->user_id !== $request->user()->id) {
            abort(403);
        }

        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:' . $plan->start_date->toDateString()],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'topic' => ['nullable', 'string', 'max:200'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'target_minutes' => ['required', 'integer', 'min:5', 'max:720'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'date.after_or_equal' => 'تاریخ آیتم باید داخل بازه برنامه باشد.',
            'target_minutes.required' => 'زمان هدف (دقیقه) الزامی است.',
        ]);

        if (! empty($data['subject_id']) && empty($data['topic'])) {
            $subject = Subject::find($data['subject_id']);
            $data['topic'] = $subject?->fullTitle();
        }

        $item = PlanItem::create([
            'plan_id' => $plan->id,
            'user_id' => $plan->user_id,
            'date' => $data['date'],
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
            'topic' => $data['topic'] ?? null,
            'target_minutes' => $data['target_minutes'],
            'completed_minutes' => 0,
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'آیتم مطالعاتی به برنامه اضافه شد.');
    }

    /** ویرایش آیتم: تغییر وضعیت به done با ثبت completed_minutes و ... */
    public function updateItem(Request $request, StudyPlan $plan, PlanItem $item)
    {
        if ($plan->user_id !== $request->user()->id || $item->plan_id !== $plan->id) {
            abort(403);
        }

        $data = $request->validate([
            'date' => ['sometimes', 'date'],
            'topic' => ['sometimes', 'nullable', 'string', 'max:200'],
            'subject_id' => ['sometimes', 'nullable', 'integer', 'exists:subjects,id'],
            'target_minutes' => ['sometimes', 'integer', 'min:5', 'max:720'],
            'completed_minutes' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1440'],
            'status' => ['sometimes', 'in:pending,partial,done,missed'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        if (array_key_exists('completed_minutes', $data)) {
            $completed = (int) ($data['completed_minutes'] ?? 0);
            $target = (int) ($data['target_minutes'] ?? $item->target_minutes);
            $data['completed_minutes'] = $completed;
            if ($data['status'] ?? false) {
                // وضعیت صریح داده شده
            } else {
                $data['status'] = $completed >= $target && $target > 0 ? 'done' : ($completed > 0 ? 'partial' : 'pending');
            }
        }

        $item->update($data);

        return back()->with('success', 'آیتم برنامه به‌روزرسانی شد.');
    }

    /** حذف آیتم برنامه */
    public function destroyItem(Request $request, StudyPlan $plan, PlanItem $item)
    {
        if ($plan->user_id !== $request->user()->id || $item->plan_id !== $plan->id) {
            abort(403);
        }

        $item->delete();

        return back()->with('success', 'آیتم از برنامه حذف شد.');
    }

    /** ثبت جلسه مطالعه واقعی (موضوع، دقیقه، تاریخ) + جمع شدن completed_minutes روی آیتم */
    public function storeSession(Request $request)
    {
        $data = $request->validate([
            'plan_item_id' => ['nullable', 'integer', 'exists:plan_items,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'date' => ['required', 'date'],
            'minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'minutes.required' => 'مدت مطالعه (دقیقه) الزامی است.',
        ]);

        $user = $request->user();
        $planItem = null;

        if (! empty($data['plan_item_id'])) {
            $planItem = PlanItem::query()->where('user_id', $user->id)->find($data['plan_item_id']);
            if (! $planItem) {
                abort(403);
            }
            $data['subject_id'] = $data['subject_id'] ?? $planItem->subject_id;
        }

        StudySession::create([
            'user_id' => $user->id,
            'plan_item_id' => $planItem?->id,
            'subject_id' => $data['subject_id'] ?? null,
            'date' => $data['date'],
            'minutes' => $data['minutes'],
            'note' => $data['note'] ?? null,
        ]);

        // جمع دقیقه‌ها روی آیتم برنامه و به‌روزرسانی وضعیت partial/done
        if ($planItem) {
            $planItem->completed_minutes = min((int) $planItem->target_minutes, (int) $planItem->completed_minutes + (int) $data['minutes']);
            $planItem->status = $planItem->completed_minutes >= (int) $planItem->target_minutes ? 'done' : 'partial';
            $planItem->save();
        }

        return back()->with('success', 'جلسه مطالعه ثبت شد.' . ($planItem ? ' پیشرفت آیتم برنامه به‌روزرسانی شد.' : ''));
    }

    /** هدف روزانه/هفتگی/ماهانه */
    public function storeGoal(Request $request)
    {
        $data = $request->validate([
            'period' => ['required', 'in:daily,weekly,monthly'],
            'target_minutes' => ['required_without:target_questions', 'nullable', 'integer', 'min:10', 'max:20000'],
            'target_questions' => ['required_without:target_minutes', 'nullable', 'integer', 'min:1', 'max:10000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ], [
            'period.required' => 'بازه هدف الزامی است.',
            'target_minutes.required_without' => 'حداقل یکی از اهداف دقیقه یا تعداد تست را وارد کنید.',
            'target_questions.required_without' => 'حداقل یکی از اهداف دقیقه یا تعداد تست را وارد کنید.',
        ]);

        Goal::create([
            'user_id' => $request->user()->id,
            'period' => $data['period'],
            'target_minutes' => $data['target_minutes'] ?? null,
            'target_questions' => $data['target_questions'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ]);

        return back()->with('success', 'هدف مطالعاتی ثبت شد.');
    }

    /** بازطراحی هوشمند: انتقال missed به روزهای بعد + بازتوزیع باقی‌مانده بر اساس هدف هفتگی */
    public function replan(Request $request, StudyPlan $plan)
    {
        if ($plan->user_id !== $request->user()->id) {
            abort(403);
        }

        $items = PlanItem::query()->where('plan_id', $plan->id)->orderBy('date')->get();
        $today = now()->startOfDay();

        // ۱) آیتم‌های عقب‌افتاده (missed یا pending تاریخ گذشته) را به روزهای بعد منتقل کن
        $missed = $items->filter(fn ($i) => in_array($i->status, ['missed', 'pending', 'partial'], true) && $i->date->lt($today));
        $moved = 0;

        foreach ($missed as $item) {
            // آیتم partial با پیشرفت واقعی: فقط دقیقه‌های باقی‌مانده را برنامه‌ریزی کن
            $remaining = max(0, (int) $item->target_minutes - (int) $item->completed_minutes);
            if ($remaining === 0) {
                $item->update(['status' => 'done']);
                continue;
            }

            $nextDay = $this->nextFreeDay($plan, $today->copy(), $item->date);
            if ($nextDay->gt($plan->end_date->endOfDay())) {
                break; // جا در بازه برنامه نیست
            }

            $item->update(['date' => $nextDay->toDateString(), 'target_minutes' => $remaining, 'status' => 'pending']);
            $moved++;
        }

        // ۲) بازتوزیع بار هفتگی بر اساس weekly_target_minutes باقی‌مانده
        // مجموع دقیقه‌های هفته جاری برنامه = مبنا؛ اختلاف با هدف هفتگی را بین روزهای خالی توزیع می‌کنیم
        $weekStart = $today->copy()->startOfWeek();
        $weekEnd = $weekStart->copy()->addDays(6);
        $weekItems = PlanItem::query()->where('plan_id', $plan->id)
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])->get();
        $weekLoad = (int) $weekItems->sum('target_minutes');
        $target = (int) ($plan->weekly_target_minutes ?? $weekLoad);
        $surplus = $target - $weekLoad; // اگر مثبت است یعنی باید دقیقه اضافه کنیم، منفی یعنی سبک‌تر کنیم

        $redistributed = 0;
        if ($surplus !== 0 && $weekItems->isNotEmpty()) {
            $future = $weekItems->filter(fn ($i) => $i->date->gte($today) && $i->status !== 'done');
            if ($future->isNotEmpty()) {
                // توزیع اختلاف به‌طور مساوی روی آیتم‌های آینده هفته (حداقل ۱۰ دقیقه برای هر آیتم)
                $share = (int) floor(abs($surplus) / $future->count());
                foreach ($future as $item) {
                    if ($share < 10) {
                        break;
                    }
                    if ($surplus > 0) {
                        $item->target_minutes = min(720, (int) $item->target_minutes + $share);
                    } else {
                        $item->target_minutes = max(10, (int) $item->target_minutes - $share);
                    }
                    $item->save();
                    $redistributed++;
                }
            }
        }

        $plan->update(['generated_by' => 'smart']);

        \App\Models\AuditLog::record($request->user(), 'plan.replanned', $plan, ['moved' => $moved, 'redistributed' => $redistributed]);

        return back()->with('success', 'بازطراحی انجام شد: ' . fa_num($moved) . ' آیتم عقب‌افتاده منتقل و ' . fa_num($redistributed) . ' آیتم بازتوزیع شد.');
    }

    /** گزارش استمرار: نمودار ۱۴ روز + مقایسه برنامه/انجام + درصد استمرار */
    public function report(Request $request)
    {
        $user = $request->user();
        $days = [];
        $labels = [];

        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $planned = (int) PlanItem::query()->where('user_id', $user->id)->where('date', $date)->sum('target_minutes');
            $done = (int) PlanItem::query()->where('user_id', $user->id)->where('date', $date)->sum('completed_minutes');
            $sessions = (int) StudySession::query()->where('user_id', $user->id)->where('date', $date)->sum('minutes');

            $days[] = ['date' => $date, 'planned' => $planned, 'completed' => max($done, $sessions)];
            $labels[] = fa_date($date, 'm/d');
        }

        $withPlan = array_filter($days, fn ($d) => $d['planned'] > 0);
        // درصد استمرار: روزهایی که حداقل ۶۰٪ برنامه انجام شده
        $consistentDays = count(array_filter($withPlan, fn ($d) => $d['completed'] >= $d['planned'] * 0.6));
        $consistency = percent($consistentDays, count($withPlan));

        $totalPlanned = array_sum(array_column($days, 'planned'));
        $totalCompleted = array_sum(array_column($days, 'completed'));
        $totalSessions = (int) StudySession::query()->where('user_id', $user->id)->where('date', '>=', now()->subDays(13)->toDateString())->count();

        // وضعیت آیتم‌ها در ۱۴ روز
        $statusCounts = PlanItem::query()->where('user_id', $user->id)
            ->where('date', '>=', now()->subDays(13)->toDateString())
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')->toArray();

        return view('planning.report', [
            'labels' => $labels,
            'days' => $days,
            'consistency' => round($consistency),
            'totalPlanned' => $totalPlanned,
            'totalCompleted' => $totalCompleted,
            'totalSessions' => $totalSessions,
            'statusCounts' => $statusCounts,
        ]);
    }

    /** اولین روز بعد از امروز که آن روز آیتم دیگری با همان بار ندارد (برای انتقال missed) */
    private function nextFreeDay(StudyPlan $plan, Carbon $from, Carbon $originalDate): Carbon
    {
        $existing = PlanItem::query()->where('plan_id', $plan->id)
            ->pluck('date')->map(fn ($d) => $d->toDateString())->all();

        $candidate = $from->copy();
        for ($i = 0; $i < 60; $i++) {
            $key = $candidate->toDateString();
            if (! in_array($key, $existing, true) || $key === $originalDate->toDateString()) {
                return $candidate;
            }
            $candidate->addDay();
        }

        return $candidate;
    }
}
