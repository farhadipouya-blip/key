@extends('layouts.app')

@section('title', $exam->title)
@section('page_title', $exam->title)

@section('content')
<x-card :title="$exam->title" icon="bi-ui-checks">
    <p class="text-muted">{{ $exam->description }}</p>
    <div class="row g-3 mb-3">
        <div class="col-md-3"><strong class="small d-block text-muted">تعداد سؤال</strong>{{ fa_num($exam->questions_count) }}</div>
        <div class="col-md-3"><strong class="small d-block text-muted">زمان</strong>{{ $exam->duration_minutes ? fa_num($exam->duration_minutes) . ' دقیقه' : 'نامحدود' }}</div>
        <div class="col-md-3"><strong class="small d-block text-muted">بارم کل</strong>{{ fa_num($exam->total_marks) }}</div>
        <div class="col-md-3"><strong class="small d-block text-muted">نمره منفی</strong>{{ $exam->negative_marking ? 'دارد (' . fa_num($exam->negative_value) . ')' : 'ندارد' }}</div>
    </div>

    @if ($canEnter)
        <form method="POST" action="{{ route('exams.start', $exam) }}" data-confirm="آیا آماده شروع آزمون هستید؟">
            @csrf
            <button class="btn btn-primary"><i class="bi bi-play-fill"></i> شروع آزمون</button>
        </form>
    @else
        <div class="alert alert-warning mb-0">این آزمون در بازه زمانی فعلی فعال نیست.</div>
    @endif
</x-card>

<x-card title="تلاش‌های قبلی من" icon="bi-clock-history" bodyClass="py-0">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>#</th><th>وضعیت</th><th>درصد</th><th>شروع</th><th></th></tr></thead>
            <tbody>
            @forelse ($myAttempts as $attempt)
                <tr>
                    <td>{{ fa_num($attempt->attempt_number) }}</td>
                    <td>
                        <x-badge :status="match($attempt->status){'in_progress'=>'warning','finished'=>'success','abandoned'=>'secondary',default=>'secondary'}"
                                 :labels="['in_progress'=>'در جریان','finished'=>'پایان‌یافته','abandoned'=>'متروکه','secondary'=>'—']"/>
                    </td>
                    <td>{{ $attempt->finished_at ? fa_num($attempt->percent) . '٪' : '—' }}</td>
                    <td class="small text-muted">{{ fa_datetime($attempt->started_at) }}</td>
                    <td>
                        @if ($attempt->status === 'in_progress')
                            <a href="{{ route_if('exams.take', $attempt) }}" class="btn btn-sm btn-success">ادامه</a>
                        @else
                            <a href="{{ route_if('exams.result', $attempt) }}" class="btn btn-sm btn-outline-primary">کارنامه</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">تلاشی ثبت نشده است</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
