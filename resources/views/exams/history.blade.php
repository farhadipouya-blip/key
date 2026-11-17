@extends('layouts.app')

@section('title', 'تاریخچه آزمون')
@section('page_title', 'تاریخچه آزمون‌های من')

@section('content')
<x-card title="تاریخچه کامل" icon="bi-clock-history" bodyClass="py-0">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr><th>آزمون</th><th>درصد</th><th>صحیح</th><th>غلط</th><th>نزده</th><th>تاریخ</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($attempts as $attempt)
                <tr>
                    <td class="small fw-bold">{{ $attempt->exam->title }}</td>
                    <td>
                        @if ($attempt->status === 'finished')
                            <span class="badge badge-soft-{{ $attempt->percent >= 60 ? 'success' : ($attempt->percent >= 35 ? 'warning' : 'danger') }}">
                                {{ fa_num($attempt->percent) }}٪
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="small text-success">{{ fa_num($attempt->correct_count) }}</td>
                    <td class="small text-danger">{{ fa_num($attempt->wrong_count) }}</td>
                    <td class="small text-muted">{{ fa_num($attempt->blank_count) }}</td>
                    <td class="small text-muted">{{ fa_date($attempt->finished_at ?: $attempt->started_at) }}</td>
                    <td>
                        @if ($attempt->status === 'in_progress')
                            <a href="{{ route_if('exams.take', $attempt) }}" class="btn btn-sm btn-success">ادامه</a>
                        @else
                            <a href="{{ route_if('exams.result', $attempt) }}" class="btn btn-sm btn-outline-primary">کارنامه</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-4"><x-empty icon="bi-ui-checks" title="آزمونی یافت نشد" actionLabel="مرکز آزمون" actionUrl="{{ route_if('exams.hub') }}"/></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>
{{ $attempts->links() }}
@endsection
