@extends('layouts.app')

@section('title', 'کلاس‌های آنلاین')
@section('page_title', 'کلاس‌های آنلاین')

@php
    $upcoming = $classes->filter(fn ($c) => $c->starts_at && $c->starts_at->isFuture());
    $past = $classes->filter(fn ($c) => ! $c->starts_at || ! $c->starts_at->isFuture());
@endphp

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-md-4">
        <x-stat icon="bi-calendar-plus" label="کلاس‌های پیش‌رو" value="{{ fa_num($upcoming->count()) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-camera-video" label="کل کلاس‌های من" value="{{ fa_num($classes->count()) }}" color="info"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-person-check" label="حضور ثبت‌شده من" value="{{ fa_num($myAttendance->count()) }}" color="success"/>
    </div>
</div>

<h5 class="fw-bold mb-3"><i class="bi bi-calendar2-week text-primary"></i> کلاس‌های پیش‌رو</h5>
<div class="row g-3 mb-4">
    @forelse ($upcoming as $class)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="fw-bold mb-0">{{ $class->title }}</h6>
                        <x-badge :status="$class->status"
                                 :labels="['scheduled'=>'زمان‌بندی‌شده','live'=>'در حال برگزاری','finished'=>'برگزار شد','canceled'=>'لغو شد']"/>
                    </div>
                    @if ($class->course)
                        <span class="badge badge-soft-info mb-2">{{ $class->course->title }}</span>
                    @endif
                    @if ($class->description)
                        <p class="text-muted small mb-2">{{ \Illuminate\Support\Str::limit($class->description, 120) }}</p>
                    @endif
                    <div class="small text-muted mb-3">
                        <div><i class="bi bi-calendar3"></i> {{ $class->starts_at ? fa_datetime($class->starts_at) : 'زمان نامشخص' }}</div>
                        @if ($class->duration_minutes)
                            <div><i class="bi bi-clock"></i> مدت: {{ duration_human($class->duration_minutes) }}</div>
                        @endif
                        @if ($class->teacher)
                            <div><i class="bi bi-person"></i> مدرس: {{ $class->teacher->name }}</div>
                        @endif
                        <div><i class="bi bi-people"></i> {{ fa_num($class->attendances_count) }} نفر حضور داشته‌اند</div>
                    </div>
                    @if (isset($myAttendance[$class->id]))
                        <div class="alert alert-success py-2 small mb-0">
                            <i class="bi bi-check-circle"></i> حضور شما در این کلاس ثبت شده است.
                        </div>
                    @else
                        <form method="POST" action="{{ route('academy.classes.join', $class) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-box-arrow-in-right"></i> ثبت حضور و ورود به کلاس</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <x-empty icon="bi-calendar-plus" title="کلاس پیش‌رویی ندارید" message="کلاس‌های آنلاین دوره‌های ثبت‌نام‌شده شما اینجا نمایش داده می‌شوند."/>
        </div>
    @endforelse
</div>

@if ($past->isNotEmpty())
    <h5 class="fw-bold mb-3"><i class="bi bi-archive text-secondary"></i> کلاس‌های گذشته</h5>
    <x-card bodyClass="py-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>کلاس</th><th>دوره</th><th>زمان</th><th>وضعیت</th><th>حضور من</th></tr></thead>
                <tbody>
                @foreach ($past as $class)
                    <tr>
                        <td class="small fw-bold">{{ $class->title }}</td>
                        <td class="small">{{ $class->course?->title ?? '—' }}</td>
                        <td class="small text-muted">{{ $class->starts_at ? fa_datetime($class->starts_at) : '—' }}</td>
                        <td>
                            <x-badge :status="$class->status"
                                     :labels="['scheduled'=>'زمان‌بندی‌شده','live'=>'در حال برگزاری','finished'=>'برگزار شد','canceled'=>'لغو شد']"/>
                        </td>
                        <td>
                            @if (isset($myAttendance[$class->id]) && $myAttendance[$class->id]->status === 'joined')
                                <span class="badge badge-soft-success">حاضر بودید</span>
                            @else
                                <span class="badge badge-soft-danger">حاضر نبودید</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
@endif
@endsection
