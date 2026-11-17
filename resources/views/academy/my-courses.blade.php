@extends('layouts.app')

@section('title', 'دوره‌های من')
@section('page_title', 'دوره‌های من')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-book" label="دوره‌های ثبت‌نام‌شده" value="{{ fa_num($enrollments->total()) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-check2-circle" label="تکمیل‌شده (۱۰۰٪)" value="{{ fa_num($enrollments->where('progress', 100)->count()) }}" color="success"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-hourglass-split" label="در حال یادگیری" value="{{ fa_num($enrollments->filter(fn ($e) => $e->progress > 0 && $e->progress < 100)->count()) }}" color="warning"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-mortarboard" label="مشاهده همه دوره‌ها" value="فهرست دوره‌ها" color="info" url="{{ route('academy.courses.index') }}"/>
    </div>
</div>

<div class="row g-3">
    @forelse ($enrollments as $enrollment)
        @php
            $course = $coursesById->get($enrollment->course_id);
            $done = (int) ($doneCounts[$enrollment->course_id] ?? 0);
            $lessonCount = (int) ($course->lessons_count ?? 0);
        @endphp
        @if (! $course) @continue @endif
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="fw-bold mb-0">
                            <a href="{{ route('academy.courses.show', $course) }}" class="text-decoration-none text-body">{{ $course->title }}</a>
                        </h6>
                        @if ($enrollment->progress >= 100)
                            <span class="badge badge-soft-success">تکمیل شد</span>
                        @elseif ($enrollment->progress > 0)
                            <span class="badge badge-soft-warning">در حال یادگیری</span>
                        @else
                            <span class="badge badge-soft-secondary">شروع‌نشده</span>
                        @endif
                    </div>
                    @if ($course->teacher)
                        <div class="small text-muted mb-2"><i class="bi bi-person"></i> مدرس: {{ $course->teacher->name }}</div>
                    @endif
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span><i class="bi bi-collection-play"></i> {{ fa_num($done) }} از {{ fa_num($lessonCount) }} درس تکمیل شده</span>
                        <strong>{{ fa_num($enrollment->progress) }}٪</strong>
                    </div>
                    <div class="progress mb-3" style="height:8px">
                        <div class="progress-bar {{ $enrollment->progress >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ $enrollment->progress }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="small text-muted">
                            <i class="bi bi-calendar3"></i> ثبت‌نام: {{ fa_date($enrollment->activated_at) }}
                            @if ($enrollment->expires_at)
                                — اعتبار تا {{ fa_date($enrollment->expires_at) }}
                            @endif
                        </span>
                        @if ($lessonCount > 0)
                            <a href="{{ route('academy.courses.show', $course) }}" class="btn btn-sm {{ $enrollment->progress >= 100 ? 'btn-outline-success' : 'btn-primary' }}">
                                {{ $enrollment->progress >= 100 ? 'مرور دوره' : 'ادامه یادگیری' }}
                            </a>
                        @else
                            <span class="small text-muted">درسی ثبت نشده است</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <x-empty icon="bi-book" title="هنوز در هیچ دوره‌ای ثبت‌نام نکرده‌اید"
                     message="از فهرست دوره‌های آکادمی، دوره مناسب خود را انتخاب و ثبت‌نام کنید."
                     actionLabel="مشاهده دوره‌ها" actionUrl="{{ route('academy.courses.index') }}"/>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $enrollments->links() }}
</div>
@endsection
