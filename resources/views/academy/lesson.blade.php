@extends('layouts.app')

@section('title', $lesson->title . ' — ' . $course->title)
@section('page_title', $course->title . ' — درس: ' . $lesson->title)

@section('content')
<div class="row g-3">
    {{-- ستون اصلی: پخش‌کننده --}}
    <div class="col-lg-8">
        <x-card bodyClass="p-2">
            @if ($lesson->type === 'video')
                @if ($lesson->video_path)
                    <video src="{{ storage_public_url($lesson->video_path) }}" controls class="w-100 rounded" style="max-height:480px; background:#000">
                        مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.
                    </video>
                @else
                    <div class="text-center py-5 bg-light rounded">
                        <i class="bi bi-camera-video-off text-muted" style="font-size:3rem"></i>
                        <p class="text-muted mt-3 mb-0">فایل ویدیویی این درس هنوز بارگذاری نشده است.</p>
                    </div>
                @endif
            @elseif ($lesson->type === 'pdf')
                @if ($media && ($media->disk ?? null) === 'public')
                    <iframe src="{{ storage_public_url($media->path) }}" class="w-100 rounded border" style="height:520px" title="{{ $lesson->title }}"></iframe>
                @elseif ($lesson->video_path)
                    <iframe src="{{ storage_public_url($lesson->video_path) }}" class="w-100 rounded border" style="height:520px" title="{{ $lesson->title }}"></iframe>
                @else
                    <div class="text-center py-5 bg-light rounded">
                        <i class="bi bi-file-earmark-pdf text-muted" style="font-size:3rem"></i>
                        <p class="text-muted mt-3 mb-0">فایل PDF این درس هنوز بارگذاری نشده است.</p>
                    </div>
                @endif
            @else
                <div class="p-3 content-fa lh-lg">
                    {!! nl2br(e($lesson->content ?: 'متن این درس ثبت نشده است.')) !!}
                </div>
            @endif
        </x-card>

        @if ($lesson->description)
            <x-card title="درباره این درس" icon="bi-info-circle">
                <p class="mb-0 text-muted">{{ $lesson->description }}</p>
            </x-card>
        @endif

        <div class="d-flex justify-content-between flex-wrap gap-2 mt-2">
            @if ($prev)
                <a href="{{ route('academy.lesson', [$course, $prev]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> درس قبلی: {{ $prev->title }}</a>
            @else
                <a href="{{ route('academy.courses.show', $course) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> فهرست درس‌ها</a>
            @endif
            @if ($next)
                <a href="{{ route('academy.lesson', [$course, $next]) }}" class="btn btn-outline-secondary">درس بعدی: {{ $next->title }} <i class="bi bi-arrow-left"></i></a>
            @endif
        </div>
    </div>

    {{-- ستون کناری: تکمیل درس و فهرست --}}
    <div class="col-lg-4">
        <x-card title="وضعیت درس" icon="bi-check2-circle">
            @if ($enrolled)
                @if ($progress && $progress->completed_at)
                    <div class="alert alert-success d-flex gap-2 align-items-center py-2">
                        <i class="bi bi-check-circle"></i>
                        <div class="small">این درس را در {{ fa_datetime($progress->completed_at) }} تکمیل کرده‌اید.</div>
                    </div>
                @else
                    <form method="POST" action="{{ route('academy.lesson.complete', [$course, $lesson]) }}">
                        @csrf
                        <input type="hidden" name="seconds_spent" value="60">
                        <button type="submit" class="btn btn-success w-100"><i class="bi bi-check2-all"></i> علامت‌گذاری به‌عنوان تکمیل‌شده</button>
                    </form>
                @endif
                <div class="mt-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>پیشرفت دوره</span>
                        <strong>{{ fa_num($course->enrollments()->where('user_id', auth()->id())->value('progress') ?? 0) }}٪</strong>
                    </div>
                    <div class="progress" style="height:8px">
                        <div class="progress-bar bg-success" style="width: {{ $course->enrollments()->where('user_id', auth()->id())->value('progress') ?? 0 }}%"></div>
                    </div>
                </div>
            @else
                <div class="alert alert-info small mb-2">
                    <i class="bi bi-unlock"></i> شما در حال مشاهده پیش‌نمایش رایگان این درس هستید.
                </div>
                <form method="POST" action="{{ route('academy.enroll', $course) }}">
                    @csrf
                    @if ($course->is_free)
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle"></i> ثبت‌نام رایگان برای دسترسی کامل</button>
                    @else
                        <a href="{{ route_if('shop.index') }}" class="btn btn-warning w-100"><i class="bi bi-cart-plus"></i> خرید دوره</a>
                    @endif
                </form>
            @endif
        </x-card>

        <x-card title="درس‌های دوره" icon="bi-list-ol" bodyClass="py-2">
            @foreach ($lessons as $i => $l)
                @php
                    $isDone = $l->id === $lesson->id ? 'current' : 'other';
                @endphp
                <a href="{{ route('academy.lesson', [$course, $l]) }}"
                   class="d-flex align-items-center gap-2 p-2 rounded {{ $l->id === $lesson->id ? 'bg-primary-subtle fw-bold' : 'text-body text-decoration-none' }}">
                    @if ($l->type === 'video')<i class="bi bi-camera-video text-primary"></i>
                    @elseif ($l->type === 'pdf')<i class="bi bi-file-earmark-pdf text-danger"></i>
                    @else<i class="bi bi-file-text text-info"></i>@endif
                    <span class="small">{{ fa_num($i + 1) }}. {{ $l->title }}</span>
                    @if ($l->is_free_preview && ! $enrolled)
                        <span class="badge badge-soft-primary ms-auto">پیش‌نمایش</span>
                    @endif
                </a>
            @endforeach
        </x-card>
    </div>
</div>
@endsection
