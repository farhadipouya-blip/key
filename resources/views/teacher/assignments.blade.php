@extends('layouts.app')

@section('title', 'تصحیح تکالیف')
@section('page_title', 'تصحیح تکالیف')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-clipboard-check" label="تکالیف من" value="{{ fa_num($stats['assignments']) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-send" label="ارسال‌ها" value="{{ fa_num($stats['submissions']) }}" color="info"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-hourglass-split" label="در انتظار تصحیح" value="{{ fa_num($stats['pending']) }}" color="warning"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-check2-circle" label="تصحیح‌شده" value="{{ fa_num($stats['graded']) }}" color="success"/>
    </div>
</div>

@forelse ($assignments as $assignment)
    <x-card title="{{ $assignment->title }}" icon="bi-clipboard2-check"
            bodyClass="py-2"
            actions="<span class='badge badge-soft-primary'>{{ $assignment->course?->title ?? 'عمومی' }}</span>">
        <div class="small text-muted mb-2">
            @if ($assignment->due_at)
                <i class="bi bi-calendar-event"></i> مهلت: {{ fa_datetime($assignment->due_at) }}
            @endif
            — حداکثر نمره: {{ fa_num($assignment->max_score) }} — تعداد ارسال: {{ fa_num($assignment->submissions_count) }}
        </div>

        @if ($assignment->submissions->isEmpty())
            <div class="text-muted small py-2"><i class="bi bi-inbox"></i> هنوز ارسالی برای این تکلیف ثبت نشده است.</div>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr><th>دانشجو</th><th>ارسال</th><th>پاسخ</th><th>فایل</th><th>وضعیت</th><th>نمره فعلی</th><th style="min-width:290px">ثبت نمره</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($assignment->submissions as $submission)
                        <tr>
                            <td class="small fw-bold">
                                {{ $submission->user?->name }}
                                <div class="text-muted" dir="ltr"><small>{{ $submission->user?->email }}</small></div>
                            </td>
                            <td class="small text-muted">{{ fa_datetime($submission->submitted_at) }}</td>
                            <td class="small" style="max-width:200px">
                                {{ \Illuminate\Support\Str::limit($submission->text_answer ?: '—', 80) }}
                                @if ($submission->text_answer)
                                    <button class="btn btn-sm btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#ans-{{ $submission->id }}">متن کامل</button>
                                @endif
                            </td>
                            <td class="small">
                                @if ($submission->file_path)
                                    <a href="{{ storage_public_url($submission->file_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i></a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <x-badge :status="$submission->status"
                                         :labels="['submitted'=>'در انتظار تصحیح','graded'=>'تصحیح‌شده','returned'=>'برگشتی']"/>
                            </td>
                            <td class="small fw-bold">{{ $submission->score !== null ? fa_num($submission->score) : '—' }}</td>
                            <td>
                                @if ($submission->status === 'graded')
                                    <div class="small text-success mb-1"><i class="bi bi-check2"></i> تصحیح توسط {{ $submission->grader?->name ?? '—' }}</div>
                                    @if ($submission->feedback)
                                        <div class="small text-muted mb-1">{{ \Illuminate\Support\Str::limit($submission->feedback, 60) }}</div>
                                    @endif
                                @endif
                                <form method="POST" action="{{ route('teacher.assignment.grade', $submission) }}" class="d-flex gap-1 align-items-center">
                                    @csrf
                                    <input type="number" name="score" class="form-control form-control-sm" style="max-width:80px"
                                           placeholder="{{ fa_num($assignment->max_score) }}" min="0" max="{{ $assignment->max_score }}" step="0.25" required>
                                    <select name="status" class="form-select form-select-sm" style="max-width:120px">
                                        <option value="graded">تصحیح‌شده</option>
                                        <option value="returned">برگشت برای اصلاح</option>
                                    </select>
                                    <input type="text" name="feedback" class="form-control form-control-sm" placeholder="بازخورد (اختیاری)"
                                           value="{{ $submission->feedback }}">
                                    <button class="btn btn-sm btn-primary" title="ثبت نمره"><i class="bi bi-check-lg"></i></button>
                                </form>
                            </td>
                        </tr>
                        @if ($submission->text_answer)
                            <tr class="collapse" id="ans-{{ $submission->id }}">
                                <td colspan="7">
                                    <div class="border rounded p-2 bg-light small lh-lg">{!! nl2br(e($submission->text_answer)) !!}</div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
@empty
    <x-empty icon="bi-clipboard-check" title="تکلیفی برای دوره‌های شما تعریف نشده است"
             message="مدیر سامانه تکالیف دوره‌های شما را ایجاد می‌کند."
             actionLabel="دوره‌های من" actionUrl="{{ route('teacher.courses') }}"/>
@endforelse

<div class="mt-3">
    {{ $assignments->links() }}
</div>
@endsection
