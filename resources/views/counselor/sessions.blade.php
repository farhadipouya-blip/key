@extends('layouts.app')

@section('title', 'جلسات مشاوره')
@section('page_title', 'جلسات مشاوره')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-4">
        <x-stat icon="bi-calendar-heart" label="کل جلسات" value="{{ fa_num($sessions->count()) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-clock" label="جلسات پیش‌رو" value="{{ fa_num($upcoming->count()) }}" color="warning"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-check2-circle" label="انجام‌شده" value="{{ fa_num($sessions->where('status', 'done')->count()) }}" color="success"/>
    </div>
</div>

<x-card title="ثبت جلسه جدید" icon="bi-calendar-plus" class="mb-3">
    <form method="POST" action="{{ route('counselor.sessions.store') }}" class="row g-3 align-items-end">
        @csrf
        <div class="col-md-3">
            <x-select name="student_id" label="دانشجو" :options="$students->pluck('name', 'id')" :value="old('student_id')" required/>
        </div>
        <div class="col-md-3">
            <x-input name="scheduled_at" label="زمان جلسه (مثال: 1404/09/01 10:30)" type="text" :value="old('scheduled_at')" required/>
        </div>
        <div class="col-md-2">
            <x-input name="duration_minutes" label="مدت (دقیقه)" type="number" :value="old('duration_minutes', 45)" min="5" max="480"/>
        </div>
        <div class="col-md-2">
            <x-select name="mode" label="نوع جلسه" :options="['online' => 'آنلاین', 'phone' => 'تلفنی', 'in_person' => 'حضوری']" :value="old('mode', 'online')" required/>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100"><i class="bi bi-check2"></i> ثبت جلسه</button>
        </div>
        <div class="col-12">
            <x-textarea name="note" label="موضوع / یادداشت (اختیاری)" :value="old('note')" rows="2"/>
        </div>
    </form>
</x-card>

<x-card title="جلسات پیش‌رو" icon="bi-clock-history" bodyClass="py-0" class="mb-3">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr><th>دانشجو</th><th>زمان</th><th>نوع</th><th>وضعیت</th><th>به‌روزرسانی</th></tr>
            </thead>
            <tbody>
            @forelse ($upcoming as $session)
                <tr>
                    <td class="small fw-bold">{{ $session->student?->name ?? '—' }}</td>
                    <td class="small">{{ fa_datetime($session->scheduled_at) }}</td>
                    <td class="small">{{ ['online' => 'آنلاین', 'phone' => 'تلفنی', 'in_person' => 'حضوری'][$session->mode] ?? $session->mode }}</td>
                    <td><x-badge :status="$session->status" :labels="['scheduled' => 'برنامه‌ریزی‌شده', 'done' => 'انجام‌شده', 'canceled' => 'لغو شده']"/></td>
                    <td>
                        <form method="POST" action="{{ route('counselor.sessions.update', $session) }}" class="d-flex gap-1 align-items-center">
                            @csrf
                            @method('PUT')
                            <select name="status" class="form-select form-select-sm" style="max-width:130px">
                                <option value="done">انجام شد</option>
                                <option value="canceled">لغو</option>
                                <option value="scheduled">تعویق</option>
                            </select>
                            <button class="btn btn-sm btn-outline-primary" title="به‌روزرسانی"><i class="bi bi-check2"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-4"><x-empty icon="bi-calendar-x" title="جلسه‌ای در پیش ندارید" message="با فرم بالا جلسه جدید ثبت کنید."/></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>

<x-card title="تاریخچه جلسات" icon="bi-clock-history" bodyClass="py-0">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr><th>دانشجو</th><th>زمان</th><th>نوع</th><th>خلاصه</th><th>وضعیت</th></tr>
            </thead>
            <tbody>
            @forelse ($sessions as $session)
                <tr>
                    <td class="small fw-bold">{{ $session->student?->name ?? '—' }}</td>
                    <td class="small">{{ fa_datetime($session->scheduled_at) }}</td>
                    <td class="small">{{ ['online' => 'آنلاین', 'phone' => 'تلفنی', 'in_person' => 'حضوری'][$session->mode] ?? $session->mode }}</td>
                    <td class="small text-truncate" style="max-width:260px">{{ $session->summary ?: '—' }}</td>
                    <td><x-badge :status="$session->status" :labels="['scheduled' => 'برنامه‌ریزی‌شده', 'done' => 'انجام‌شده', 'canceled' => 'لغو شده']"/></td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-4"><x-empty icon="bi-clock-history" title="جلسه‌ای ثبت نشده است"/></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
