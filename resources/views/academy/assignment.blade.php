@extends('layouts.app')

@section('title', $assignment->title . ' — تکلیف')
@section('page_title', $assignment->title)

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <x-card title="شرح تکلیف" icon="bi-clipboard-check">
            @if ($assignment->course)
                <a href="{{ route('academy.courses.show', $assignment->course) }}" class="badge badge-soft-primary mb-2 text-decoration-none">{{ $assignment->course->title }}</a>
            @endif
            <p class="lh-lg mb-2">{!! nl2br(e($assignment->description)) !!}</p>
            <div class="d-flex flex-wrap gap-3 small text-muted border-top pt-2">
                @if ($assignment->due_at)
                    <span class="{{ $assignment->due_at->isPast() ? 'text-danger fw-bold' : '' }}">
                        <i class="bi bi-calendar-event"></i> مهلت تحویل: {{ fa_datetime($assignment->due_at) }}
                    </span>
                @endif
                <span><i class="bi bi-star"></i> حداکثر نمره: {{ fa_num($assignment->max_score) }}</span>
            </div>
        </x-card>

        @if ($submission && $submission->feedback)
            <x-card title="بازخورد و نمره مدرس" icon="bi-pen">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="fw-bold fs-4 text-success">{{ fa_num($submission->score) }}</span>
                    <span class="text-muted small">از {{ fa_num($assignment->max_score) }}</span>
                    <x-badge :status="$submission->status" :labels="['submitted'=>'ارسال‌شده','graded'=>'تصحیح‌شده','returned'=>'برگشتی برای اصلاح']"/>
                </div>
                <p class="mb-0 text-muted lh-lg">{!! nl2br(e($submission->feedback)) !!}</p>
            </x-card>
        @endif
    </div>

    <div class="col-lg-5">
        @if ($submission && $submission->status === 'graded')
            <div class="alert alert-success d-flex gap-2">
                <i class="bi bi-check-circle"></i>
                <div>این تکلیف تصحیح شده است و امکان ارسال مجدد وجود ندارد.</div>
            </div>
        @else
            <x-card title="{{ $submission ? 'ویرایش ارسال' : 'ارسال پاسخ' }}" icon="bi-send">
                @if ($assignment->due_at && $assignment->due_at->isPast())
                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle"></i> مهلت تحویل گذشته است؛ ارسال با تأخیر ثبت می‌شود.
                    </div>
                @endif
                <form method="POST" action="{{ route('academy.assignment.submit', $assignment) }}" enctype="multipart/form-data">
                    @csrf
                    <x-textarea name="text_answer" label="متن پاسخ" rows="7"
                                value="{{ old('text_answer', $submission->text_answer ?? '') }}"
                                placeholder="پاسخ خود را بنویسید..."/>
                    <div class="mb-3">
                        <label class="form-label" for="file">فایل پیوست (اختیاری)</label>
                        <input type="file" id="file" name="file" class="form-control">
                        <div class="form-text">فرمت‌های مجاز: pdf، word، zip، تصویر، متن، صوت، ویدیو — حداکثر ۲۰ مگابایت</div>
                        @error('file')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                    </div>
                    @if ($submission && $submission->file_path)
                        <div class="small text-muted mb-3">
                            <i class="bi bi-paperclip"></i> فایل فعلی:
                            <a href="{{ storage_public_url($submission->file_path) }}" target="_blank" class="text-decoration-none">مشاهده فایل ارسال‌شده</a>
                        </div>
                    @endif
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send"></i> {{ $submission ? 'به‌روزرسانی ارسال' : 'ارسال تکلیف' }}</button>
                </form>
            </x-card>
        @endif

        @if ($submission)
            <x-card title="وضعیت ارسال من" icon="bi-info-circle">
                <div class="small">
                    <div class="mb-1"><i class="bi bi-clock"></i> ارسال: {{ fa_datetime($submission->submitted_at) }}</div>
                    <div class="mb-1"><i class="bi bi-file-earmark"></i> فایل: {{ $submission->file_path ? 'بارگذاری شده' : 'بدون فایل' }}</div>
                    @if ($submission->grader)
                        <div><i class="bi bi-person"></i> تصحیح توسط: {{ $submission->grader->name }} — {{ $submission->graded_at ? fa_datetime($submission->graded_at) : '' }}</div>
                    @endif
                </div>
            </x-card>
        @endif
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('academy.my-assignments') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> بازگشت به تکالیف من</a>
</div>
@endsection
