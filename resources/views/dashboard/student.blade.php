@extends('layouts.app')

@section('title', 'داشبورد دانشجو')
@section('page_title', 'داشبورد دانشجو')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-arrow-repeat" label="مرور امروز" value="{{ fa_num($stats['dueReviews']) }}" color="warning"
                url="{{ route('review.index') }}"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-ui-checks" label="آزمون‌های انجام‌شده" value="{{ fa_num($stats['recentTests']) }}" color="primary"
                url="{{ route_if('exams.hub') }}"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-graph-up-arrow" label="میانگین درصد اخیر" value="{{ fa_num($stats['avgPercent']) }}٪" color="success"
                url="{{ route_if('analytics.index') }}"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-clock-history" label="مطالعه امروز" value="{{ duration_human($stats['todayMinutes']) }}" color="info"
                url="{{ route_if('plan.index') }}"/>
    </div>
</div>

@if ($subscription)
    <div class="alert alert-success d-flex align-items-center gap-2 py-2">
        <i class="bi bi-star-fill"></i>
        اشتراک شما فعال است — تا تاریخ <strong>{{ fa_date($subscription->ends_at) }}</strong>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-7">
        <x-card title="برنامه امروز" icon="bi-calendar2-day"
                actions="<a href='{{ route_if('plan.index') }}' class='btn btn-sm btn-outline-primary'>مدیریت برنامه</a>">
            @forelse ($todayPlan as $item)
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-book text-primary"></i>
                        <div>
                            <div class="fw-bold small">{{ $item->subject?->title ?? $item->topic ?: 'مطالعه' }}</div>
                            <div class="text-muted" style="font-size:.75rem">
                                {{ fa_num($item->target_minutes) }} دقیقه هدف
                                @if ($item->completed_minutes > 0) — انجام‌شده: {{ duration_human($item->completed_minutes) }} @endif
                            </div>
                        </div>
                    </div>
                    <x-badge :status="match($item->status) {'pending'=>'secondary','done'=>'success','partial'=>'warning','missed'=>'danger',default=>'secondary'}"
                             :labels="['pending'=>'در انتظار','done'=>'انجام‌شده','partial'=>'نیمه‌تمام','missed'=>'عقب‌افتاده','secondary'=>'در انتظار']"/>
                </div>
            @empty
                <x-empty icon="bi-calendar-x" title="برنامه‌ای برای امروز ندارید"
                         message="از بخش برنامه مطالعاتی یک برنامه بسازید یا ثبت جلسه مطالعه کنید."
                         actionLabel="برنامه مطالعاتی" actionUrl="{{ route_if('plan.index') }}"/>
            @endforelse
        </x-card>

        <x-card title="آزمون‌های اخیر" icon="bi-ui-checks" bodyClass="py-0"
                actions="<a href='{{ route_if('exams.history') }}' class='btn btn-sm btn-outline-primary'>تاریخچه کامل</a>">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>آزمون</th><th>درصد</th><th>صحیح/غلط/نزده</th><th>تاریخ</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($recentAttempts as $attempt)
                        <tr>
                            <td class="small">{{ $attempt->exam->title }}</td>
                            <td><strong>{{ fa_num($attempt->percent) }}٪</strong></td>
                            <td class="small text-muted">
                                {{ fa_num($attempt->correct_count) }} / {{ fa_num($attempt->wrong_count) }} / {{ fa_num($attempt->blank_count) }}
                            </td>
                            <td class="small text-muted">{{ fa_date($attempt->finished_at) }}</td>
                            <td><a href="{{ route_if('exams.result', $attempt) }}" class="btn btn-sm btn-outline-primary">کارنامه</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4">
                            <x-empty icon="bi-ui-checks" title="هنوز آزمونی نداده‌اید"
                                     actionLabel="شروع آزمون" actionUrl="{{ route_if('exams.hub') }}"/>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <div class="col-lg-5">
        <x-card title="صف مرور امروز" icon="bi-arrow-repeat"
                actions="<a href='{{ route('review.index') }}' class='btn btn-sm btn-primary'>شروع مرور</a>">
            <div class="text-center py-2">
                <div style="font-size:2.2rem;font-weight:800;color:var(--key-warning)">{{ fa_num($stats['dueReviews']) }}</div>
                <div class="text-muted small">مورد در انتظار مرور</div>
                <div class="text-muted mt-2" style="font-size:.8rem">
                    ماندگاری مطالب: {{ fa_num($reviewService->retentionStats(auth()->user())['retention']) }}٪
                </div>
            </div>
        </x-card>

        <x-card title="نقاط قوت و ضعف" icon="bi-graph-up-arrow">
            <div class="mb-2 fw-bold small text-success"><i class="bi bi-plus-circle"></i> نقاط قوت</div>
            @forelse ($mastery['strengths'] as $m)
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                    <span class="small">{{ $m->subject?->title }}</span>
                    <span class="badge badge-soft-success">{{ fa_num($m->level) }}٪</span>
                </div>
            @empty
                <div class="text-muted small">داده‌ای موجود نیست</div>
            @endforelse
            <div class="mt-3 mb-2 fw-bold small text-danger"><i class="bi bi-exclamation-circle"></i> نقاط ضعف</div>
            @forelse ($mastery['weaknesses'] as $m)
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                    <span class="small">{{ $m->subject?->title }}</span>
                    <span class="badge badge-soft-danger">{{ fa_num($m->level) }}٪</span>
                </div>
            @empty
                <div class="text-muted small">داده‌ای موجود نیست</div>
            @endforelse
        </x-card>

        <x-card title="دوره‌های من" icon="bi-book"
                actions="<a href='{{ route_if('academy.my-courses') }}' class='btn btn-sm btn-outline-primary'>همه</a>">
            @forelse ($enrollments as $en)
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="fw-bold">{{ $en->course->title }}</span>
                        <span class="text-muted">{{ fa_num($en->progress) }}٪</span>
                    </div>
                    <div class="progress" style="height:6px">
                        <div class="progress-bar bg-primary" style="width: {{ $en->progress }}%"></div>
                    </div>
                </div>
            @empty
                <x-empty icon="bi-book" title="در دوره‌ای ثبت‌نام نشده‌اید" actionLabel="مرور دوره‌ها" actionUrl="{{ route_if('academy.courses.index') }}"/>
            @endforelse
        </x-card>
    </div>
</div>
@endsection
