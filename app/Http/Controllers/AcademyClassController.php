<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\OnlineClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * مدیریت کلاس‌های آنلاین (اسکای‌روم / الوکام / اختصاصی)
 */
class AcademyClassController extends Controller
{
    private function platformOptions(): array
    {
        return [
            'skyroom' => 'اسکای‌روم',
            'alokam' => 'آلوکام',
            'custom' => 'اختصاصی (سایر)',
        ];
    }

    private function statusOptions(): array
    {
        return [
            'scheduled' => 'زمان‌بندی‌شده',
            'live' => 'در حال برگزاری',
            'finished' => 'برگزار شده',
            'canceled' => 'لغو شده',
        ];
    }

    private function courseOptions(): array
    {
        return Course::query()->orderBy('title')->pluck('title', 'id')->all();
    }

    private function teacherOptions(): array
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['teacher', 'admin', 'superadmin', 'counselor']))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->pluck('name', 'id')
            ->all();
    }

    public function index(Request $request)
    {
        $classes = OnlineClass::query()
            ->with(['course:id,title', 'teacher:id,name'])
            ->withCount('attendances')
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%' . $request->q . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('platform'), fn ($q) => $q->where('platform', $request->platform))
            ->orderByDesc('starts_at')
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'all' => OnlineClass::count(),
            'upcoming' => OnlineClass::where('starts_at', '>', now())->where('status', 'scheduled')->count(),
            'attendances' => \App\Models\ClassAttendance::count(),
        ];

        return view('admin.academy.classes.index', [
            'classes' => $classes,
            'stats' => $stats,
            'platforms' => $this->platformOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function create()
    {
        return view('admin.academy.classes.form', [
            'class' => new OnlineClass(['platform' => 'skyroom', 'status' => 'scheduled']),
            'platforms' => $this->platformOptions(),
            'statuses' => $this->statusOptions(),
            'courses' => $this->courseOptions(),
            'teachers' => $this->teacherOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateClass($request);

        $data['created_by'] = $request->user()->id;
        if (empty($data['teacher_id']) && ! empty($data['course_id'])) {
            $data['teacher_id'] = Course::find($data['course_id'])?->teacher_id;
        }
        $data['teacher_id'] = $data['teacher_id'] ?? null;

        $class = OnlineClass::create($data);

        AuditLog::record($request->user(), 'class.created', $class, ['title' => $class->title]);

        return redirect()->route('admin.classes.index')
            ->with('success', 'کلاس «' . $class->title . '» ایجاد شد.');
    }

    public function edit(OnlineClass $onlineClass)
    {
        return view('admin.academy.classes.form', [
            'class' => $onlineClass,
            'platforms' => $this->platformOptions(),
            'statuses' => $this->statusOptions(),
            'courses' => $this->courseOptions(),
            'teachers' => $this->teacherOptions(),
        ]);
    }

    public function update(Request $request, OnlineClass $onlineClass)
    {
        $data = $this->validateClass($request);
        $onlineClass->update($data);

        AuditLog::record($request->user(), 'class.updated', $onlineClass, ['title' => $onlineClass->title]);

        return redirect()->route('admin.classes.index')
            ->with('success', 'کلاس «' . $onlineClass->title . '» به‌روزرسانی شد.');
    }

    public function destroy(Request $request, OnlineClass $onlineClass)
    {
        $title = $onlineClass->title;
        $onlineClass->delete();

        AuditLog::record($request->user(), 'class.deleted', null, ['title' => $title, 'id' => $onlineClass->id]);

        return redirect()->route('admin.classes.index')
            ->with('success', 'کلاس «' . $title . '» حذف شد.');
    }

    private function validateClass(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'platform' => ['required', Rule::in(['skyroom', 'alokam', 'custom'])],
            'meeting_id' => ['nullable', 'string', 'max:100'],
            'join_url' => ['nullable', 'url', 'max:500'],
            'host_url' => ['nullable', 'url', 'max:500'],
            'starts_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', Rule::in(['scheduled', 'live', 'finished', 'canceled'])],
        ], [
            'title.required' => 'عنوان کلاس الزامی است.',
            'platform.required' => 'انتخاب پلتفرم الزامی است.',
            'platform.in' => 'پلتفرم انتخابی معتبر نیست.',
            'join_url.url' => 'آدرس ورود به کلاس باید یک لینک معتبر باشد.',
            'host_url.url' => 'آدرس میزبان باید یک لینک معتبر باشد.',
            'starts_at.date' => 'تاریخ و ساعت برگزاری معتبر نیست.',
            'duration_minutes.integer' => 'مدت کلاس باید عدد صحیح باشد.',
            'course_id.exists' => 'دوره انتخابی معتبر نیست.',
            'teacher_id.exists' => 'مدرس انتخابی معتبر نیست.',
            'status.required' => 'انتخاب وضعیت الزامی است.',
            'status.in' => 'وضعیت انتخابی معتبر نیست.',
        ]);
    }
}
