@extends('layouts.app')

@section('title', 'آزمون‌ساز')
@section('page_title', 'آزمون‌ساز')

@section('content')
<x-alerts/>

<div class="row g-3 mb-2">
    <div class="col-6 col-md-3"><x-stat icon="bi-ui-checks" label="کل آزمون‌ها" value="{{ fa_num($stats['all']) }}" color="primary"/></div>
    <div class="col-6 col-md-3"><x-stat icon="bi-check2-circle" label="منتشرشده" value="{{ fa_num($stats['published']) }}" color="success"/></div>
    <div class="col-6 col-md-3"><x-stat icon="bi-pencil" label="پیش‌نویس" value="{{ fa_num($stats['draft']) }}" color="warning"/></div>
    <div class="col-6 col-md-3"><x-stat icon="bi-people" label="مجموع شرکت‌ها" value="{{ fa_num($stats['attempts']) }}" color="info"/></div>
</div>

<x-card title="فهرست آزمون‌ها" icon="bi-ui-checks"
        actions="<a href='{{ route('admin.exams.create') }}' class='btn btn-sm btn-primary'><i class='bi bi-plus-lg'></i> آزمون جدید</a>">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-6">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="جستجوی عنوان..." value="{{ request('q') }}">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">همه وضعیت‌ها</option>
                <option value="draft" @if (request('status') === 'draft') selected @endif>پیش‌نویس</option>
                <option value="published" @if (request('status') === 'published') selected @endif>منتشرشده</option>
                <option value="closed" @if (request('status') === 'closed') selected @endif>بسته</option>
            </select>
        </div>
        <div class="col-md-1 d-grid">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>عنوان</th><th>نوع</th><th>سؤال</th><th>مجموع بارم</th><th>شرکت‌کننده</th><th>منفی</th><th>دسترسی</th><th>وضعیت</th><th style="width:150px"></th></tr>
            </thead>
            <tbody>
            @forelse ($exams as $exam)
                <tr>
                    <td class="small fw-bold">
                        {{ $exam->title }}
                        @if ($exam->subject)
                            <div class="text-muted"><i class="bi bi-book small"></i> {{ $exam->subject->title }}</div>
                        @endif
                    </td>
                    <td class="small">{{ ['practice' => 'تمرین', 'timed' => 'زمان‌دار', 'simulator' => 'شبیه‌ساز', 'homework' => 'تکلیف'][$exam->type] ?? $exam->type }}</td>
                    <td class="small">{{ fa_num($exam->questions_count) }}</td>
                    <td class="small">{{ fa_num($exam->total_marks) }}</td>
                    <td class="small">{{ fa_num($exam->attempts_count) }}</td>
                    <td class="small">
                        @if ($exam->negative_marking)
                            <span class="badge badge-soft-danger">{{ fa_num($exam->negative_value) }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="small">
                        @if ($exam->is_public)
                            <span class="badge badge-soft-success">عمومی</span>
                        @else
                            <span class="badge badge-soft-secondary">خصوصی</span>
                        @endif
                    </td>
                    <td>
                        <x-badge :status="$exam->status" :labels="['published' => 'منتشرشده', 'draft' => 'پیش‌نویس', 'closed' => 'بسته']"/>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.exams.builder', $exam) }}" class="btn btn-sm btn-outline-primary" title="سازنده آزمون"><i class="bi bi-collection"></i></a>
                            <a href="{{ route('admin.exams.edit', $exam) }}" class="btn btn-sm btn-outline-secondary" title="مشخصات"><i class="bi bi-gear"></i></a>
                            <form method="POST" action="{{ route('admin.exams.destroy', $exam) }}"
                                  onsubmit="return confirm('حذف آزمون «{{ $exam->title }}» همراه با کارنامه‌ها؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9"><x-empty icon="bi-ui-checks" title="آزمونی ساخته نشده است" actionLabel="ساخت آزمون" actionUrl="{{ route('admin.exams.create') }}"/></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>

{{ $exams->links() }}
@endsection
