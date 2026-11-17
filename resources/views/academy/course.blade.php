@extends('layouts.app')

@section('title', $course->title . ' — آکادمی')
@section('page_title', $course->title)

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-collection-play" label="تعداد درس‌ها" value="{{ fa_num($lessons->count()) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-people" label="دانشجویان دوره" value="{{ fa_num($course->enrollments()->count()) }}" color="info"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-clock-history" label="مدت دوره" value="{{ $course->duration_hours ? duration_human($course->duration_hours * 60) : '—' }}" color="warning"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-graph-up" label="پیشرفت من" value="{{ $enrolled ? fa_num($enrollment->progress) . '٪' : '۰٪' }}" color="success"/>
    </div>
</div>

<div class="card mb-3">
    @if ($course->cover_path)
        <img src="{{ storage_public_url($course->cover_path) }}" alt="{{ $course->title }}" style="height:220px;object-fit:cover" class="card-img-top">
    @endif
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <h5 class="fw-bold mb-1">{{ $course->title }}</h5>
            <div class="d-flex gap-2">
                @if ($course->is_free)
                    <span class="badge badge-soft-success">رایگان</span>
                @else
                    <span class="badge badge-soft-warning">پولی — {{ money($course->price) }}</span>
                @endif
                @if ($course->category)
                    <span class="badge badge-soft-info">{{ $course->category->title }}</span>
                @endif
            </div>
        </div>
        <p class="text-muted mt-2 mb-2">{{ $course->description ?: 'توضیحی برای این دوره ثبت نشده است.' }}</p>
        <div class="d-flex flex-wrap gap-3 small text-muted">
            @if ($course->teacher)
                <span><i class="bi bi-person-badge"></i> مدرس: <strong>{{ $course->teacher->name }}</strong></span>
            @endif
            <span><i class="bi bi-collection-play"></i> {{ fa_num($lessons->count()) }} درس</span>
        </div>
        <div class="d-flex gap-2 mt-3">
            @if (! $enrolled)
                @if ($course->is_free)
                    <form method="POST" action="{{ route('academy.enroll', $course) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> ثبت‌نام رایگان در دوره</button>
                    </form>
                @else
                    <a href="{{ route_if('shop.index') }}" class="btn btn-warning"><i class="bi bi-cart-plus"></i> خرید و دسترسی به دوره ({{ money($course->price) }})</a>
                @endif
            @else
                @if ($lessons->isNotEmpty())
                    @php $firstLesson = $lessons->first(); @endphp
                    <a href="{{ route('academy.lesson', [$course, $firstLesson]) }}" class="btn btn-success"><i class="bi bi-play-circle"></i> ادامه یادگیری</a>
                @endif
            @endif
            <a href="{{ route('academy.courses.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> بازگشت به فهرست دوره‌ها</a>
        </div>
    </div>
</div>

@if ($enrolled && $certificate)
    <div class="alert alert-success d-flex gap-2 align-items-center">
        <i class="bi bi-patch-check"></i>
        <div>
            گواهینامه تکمیل این دوره برای شما صادر شده است.
            <a href="{{ route('certificates.show', $certificate) }}" class="alert-link">مشاهده گواهی‌نامه</a>
        </div>
    </div>
@endif

<x-card title="درس‌های دوره" icon="bi-list-ol">
    @forelse ($lessons as $i => $lesson)
        @php
            $done = in_array($lesson->id, $completedIds);
            $canOpen = $enrolled || $lesson->is_free_preview;
        @endphp
        <div class="d-flex align-items-center gap-3 border-bottom py-2 {{ $loop->last ? 'border-bottom-0' : '' }}">
            <span class="badge bg-secondary rounded-circle p-2">{{ fa_num($i + 1) }}</span>
            <div class="flex-grow-1">
                <div class="fw-bold small {{ $done ? 'text-decoration-line-through text-success' : '' }}">{{ $lesson->title }}</div>
                <div class="small text-muted d-flex flex-wrap gap-3">
                    <span>
                        @if ($lesson->type === 'video')<i class="bi bi-camera-video"></i> ویدیو
                        @elseif ($lesson->type === 'pdf')<i class="bi bi-file-earmark-pdf"></i> PDF
                        @else<i class="bi bi-file-text"></i> متن
                        @endif
                    </span>
                    @if ($lesson->duration_minutes)
                        <span><i class="bi bi-clock"></i> {{ duration_human($lesson->duration_minutes) }}</span>
                    @endif
                    @if ($lesson->is_free_preview && ! $enrolled)
                        <span class="text-primary"><i class="bi bi-unlock"></i> پیش‌نمایش رایگان</span>
                    @endif
                </div>
            </div>
            @if ($done)
                <span class="badge badge-soft-success"><i class="bi bi-check-lg"></i> تکمیل شد</span>
            @endif
            @if ($canOpen)
                <a href="{{ route('academy.lesson', [$course, $lesson]) }}" class="btn btn-sm {{ $done ? 'btn-outline-secondary' : 'btn-primary' }}">
                    {{ $done ? 'مرور' : 'مشاهده' }}
                </a>
            @else
                <span class="badge badge-soft-secondary"><i class="bi bi-lock"></i> قفل</span>
            @endif
        </div>
    @empty
        <x-empty icon="bi-collection-play" title="هنوز درسی برای این دوره ثبت نشده است" message="به‌زودی درس‌ها اضافه می‌شوند."/>
    @endforelse
</x-card>
@endsection
