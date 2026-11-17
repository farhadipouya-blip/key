@extends('layouts.app')

@section('title', 'مرکز آزمون')
@section('page_title', 'بانک تست و آزمون')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-4">
        <x-stat icon="bi-check2-circle" label="آزمون‌های کامل‌شده" value="{{ fa_num($stats['total']) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-graph-up" label="میانگین درصد" value="{{ fa_num($stats['avg']) }}٪" color="success"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-trophy" label="بهترین کارنامه" value="{{ fa_num($stats['best']) }}٪" color="warning"/>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">آزمون‌های در دسترس</h5>
    <div class="d-flex gap-2">
        <a href="{{ route('exams.practice') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-database"></i> بانک تست (تمرین)</a>
        <a href="{{ route_if('exams.history') }}" class="btn btn-outline-secondary btn-sm">تاریخچه آزمون</a>
    </div>
</div>

<div class="row g-3">
    @forelse ($exams as $exam)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="fw-bold mb-0">{{ $exam->title }}</h6>
                        <x-badge :status="$exam->isOpenNow() ? 'success' : 'secondary'"
                                 :labels="['success'=>'فعال','secondary'=>'غیرفعال']"/>
                    </div>
                    @if ($exam->subject)
                        <span class="badge badge-soft-info mb-2">{{ $exam->subject->title }}</span>
                    @endif
                    <p class="text-muted small mb-2">{{ \Illuminate\Support\Str::limit($exam->description, 100) ?: 'بدون توضیح' }}</p>
                    <div class="d-flex flex-wrap gap-3 small text-muted mb-3">
                        <span><i class="bi bi-list-ol"></i> {{ fa_num($exam->questions_count) }} سؤال</span>
                        @if ($exam->duration_minutes)
                            <span><i class="bi bi-clock"></i> {{ fa_num($exam->duration_minutes) }} دقیقه</span>
                        @else
                            <span><i class="bi bi-clock"></i> بدون محدودیت زمان</span>
                        @endif
                        @if ($exam->negative_marking)
                            <span><i class="bi bi-exclamation-triangle text-danger"></i> نمره منفی</span>
                        @endif
                    </div>
                    @if ($exam->starts_at || $exam->ends_at)
                        <div class="small text-muted mb-3">
                            @if ($exam->starts_at)از {{ fa_datetime($exam->starts_at) }} @endif
                            @if ($exam->ends_at) تا {{ fa_datetime($exam->ends_at) }}@endif
                        </div>
                    @endif
                    <a href="{{ route('exams.show', $exam) }}" class="btn btn-primary btn-sm w-100">مشاهده و شروع</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <x-empty icon="bi-ui-checks" title="آزمونی منتشر نشده است" message="هنگام انتشار آزمون جدید اینجا نمایش داده می‌شود."/>
        </div>
    @endforelse
</div>

@if ($myAttempts->isNotEmpty())
    <h5 class="fw-bold mt-4 mb-3">آخرین تلاش‌های من</h5>
    <x-card bodyClass="py-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>آزمون</th><th>وضعیت</th><th>درصد</th><th>تاریخ</th><th></th></tr></thead>
                <tbody>
                @foreach ($myAttempts as $attempt)
                    <tr>
                        <td class="small fw-bold">{{ $attempt->exam->title }}</td>
                        <td>
                            <x-badge :status="match($attempt->status){'in_progress'=>'warning','finished'=>'success','abandoned'=>'secondary',default=>'secondary'}"
                                     :labels="['in_progress'=>'در جریان','finished'=>'پایان‌یافته','abandoned'=>'متروکه','secondary'=>'—']"/>
                        </td>
                        <td class="small">{{ $attempt->finished_at ? fa_num($attempt->percent) . '٪' : '—' }}</td>
                        <td class="small text-muted">{{ fa_datetime($attempt->started_at) }}</td>
                        <td>
                            @if ($attempt->status === 'in_progress')
                                <a href="{{ route_if('exams.take', $attempt) }}" class="btn btn-sm btn-success">ادامه آزمون</a>
                            @else
                                <a href="{{ route_if('exams.result', $attempt) }}" class="btn btn-sm btn-outline-primary">کارنامه</a>
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
