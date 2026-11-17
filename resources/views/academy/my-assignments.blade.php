@extends('layouts.app')

@section('title', 'تکالیف من')
@section('page_title', 'تکالیف من')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-md-4">
        <x-stat icon="bi-clipboard-check" label="تکالیف دوره‌های من" value="{{ fa_num($assignments->total()) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-hourglass-split" label="در انتظار ارسال" value="{{ fa_num($assignments->filter(fn ($a) => $a->submissions->isEmpty())->count()) }}" color="warning"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-pen" label="تصحیح‌شده" value="{{ fa_num($assignments->filter(fn ($a) => ($a->submissions->first()?->status ?? '') === 'graded')->count()) }}" color="success"/>
    </div>
</div>

<div class="row g-3">
    @forelse ($assignments as $assignment)
        @php
            $submission = $assignment->submissions->first();
            $late = $assignment->due_at && $assignment->due_at->isPast();
        @endphp
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="fw-bold mb-0">{{ $assignment->title }}</h6>
                        @if ($submission && $submission->status === 'graded')
                            <span class="badge badge-soft-success">تصحیح شد</span>
                        @elseif ($submission)
                            <span class="badge badge-soft-info">ارسال شده</span>
                        @elseif ($late)
                            <span class="badge badge-soft-danger">مهلت گذشته</span>
                        @else
                            <span class="badge badge-soft-warning">در انتظار ارسال</span>
                        @endif
                    </div>
                    @if ($assignment->course)
                        <span class="badge badge-soft-primary mb-2">{{ $assignment->course->title }}</span>
                    @endif
                    <p class="text-muted small mb-2">{{ \Illuminate\Support\Str::limit($assignment->description, 140) }}</p>
                    <div class="d-flex flex-wrap gap-3 small text-muted mb-3">
                        @if ($assignment->due_at)
                            <span class="{{ $late && ! $submission ? 'text-danger' : '' }}"><i class="bi bi-calendar-event"></i> مهلت: {{ fa_datetime($assignment->due_at) }}</span>
                        @endif
                        <span><i class="bi bi-star"></i> حداکثر نمره: {{ fa_num($assignment->max_score) }}</span>
                    </div>

                    @if ($submission)
                        <div class="border rounded p-2 bg-light small mb-2">
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-send"></i> ارسال: {{ fa_datetime($submission->submitted_at) }}</span>
                                @if ($submission->status === 'graded')
                                    <strong>نمره: {{ fa_num($submission->score) }} از {{ fa_num($assignment->max_score) }}</strong>
                                @endif
                            </div>
                            @if ($submission->feedback)
                                <div class="mt-1 text-body"><i class="bi bi-chat-dots text-primary"></i> بازخورد مدرس: {{ $submission->feedback }}</div>
                            @endif
                        </div>
                    @endif

                    <a href="{{ route('academy.assignment.show', $assignment) }}" class="btn btn-sm {{ $submission ? 'btn-outline-secondary' : 'btn-primary' }} w-100">
                        {{ $submission ? 'مشاهده ارسال و وضعیت' : 'مشاهده و ارسال تکلیف' }}
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <x-empty icon="bi-clipboard-check" title="تکلیفی برای دوره‌های شما تعریف نشده است"
                     message="پس از ثبت‌نام در دوره‌ها، تکالیف مربوطه اینجا نمایش داده می‌شود."
                     actionLabel="مشاهده دوره‌ها" actionUrl="{{ route('academy.courses.index') }}"/>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $assignments->links() }}
</div>
@endsection
