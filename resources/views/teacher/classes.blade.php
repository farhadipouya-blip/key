@extends('layouts.app')

@section('title', 'کلاس‌های من')
@section('page_title', 'کلاس‌های آنلاین من')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-md-4">
        <x-stat icon="bi-camera-video" label="کل کلاس‌ها" value="{{ fa_num($stats['all']) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-calendar-plus" label="کلاس‌های پیش‌رو" value="{{ fa_num($stats['upcoming']) }}" color="success"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-person-check" label="مجموع حضورها" value="{{ fa_num($stats['attendances']) }}" color="info"/>
    </div>
</div>

@forelse ($classes as $class)
    <x-card title="{{ $class->title }}" icon="bi-camera-video" bodyClass="py-2"
            actions="@if ($class->host_url)<a href='{{ $class->host_url }}' target='_blank' class='btn btn-sm btn-success'><i class='bi bi-door-open'></i> ورود میزبان</a>@endif @if ($class->join_url)<a href='{{ $class->join_url }}' target='_blank' class='btn btn-sm btn-outline-primary'><i class='bi bi-link-45deg'></i> لینک دانشجویان</a>@endif">
        <div class="small text-muted mb-2">
            @if ($class->course)
                <span class="badge badge-soft-primary">{{ $class->course->title }}</span>
            @endif
            <i class="bi bi-calendar3"></i> {{ $class->starts_at ? fa_datetime($class->starts_at) : 'زمان نامشخص' }}
            @if ($class->duration_minutes) — مدت: {{ duration_human($class->duration_minutes) }} @endif
            <x-badge :status="$class->status" :labels="['scheduled'=>'زمان‌بندی‌شده','live'=>'در حال برگزاری','finished'=>'برگزار شد','canceled'=>'لغو شد']"/>
        </div>

        @if ($class->attendances->isEmpty())
            <div class="text-muted small py-2"><i class="bi bi-person-x"></i> هنوز حاضری ثبت نشده است.</div>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>دانشجو</th><th>زمان حضور</th><th>وضعیت</th></tr></thead>
                    <tbody>
                    @foreach ($class->attendances as $attendance)
                        <tr>
                            <td class="small fw-bold">{{ $attendance->user?->name }} <span class="text-muted" dir="ltr"><small>({{ $attendance->user?->email }})</small></span></td>
                            <td class="small text-muted">{{ $attendance->joined_at ? fa_datetime($attendance->joined_at) : '—' }}</td>
                            <td>
                                <x-badge :status="$attendance->status === 'joined' ? 'success' : 'danger'"
                                         :labels="['success'=>'حاضر','danger'=>'غایب']"/>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
@empty
    <x-empty icon="bi-camera-video" title="کلاسی برای شما تعریف نشده است"
             message="مدیر سامانه کلاس‌های آنلاین شما را ایجاد می‌کند."
             actionLabel="دوره‌های من" actionUrl="{{ route('teacher.courses') }}"/>
@endforelse
@endsection
