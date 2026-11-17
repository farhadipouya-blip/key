@extends('layouts.app')

@section('title', 'مدیریت تکالیف')
@section('page_title', 'مدیریت تکالیف')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-clipboard-check" label="کل تکالیف" value="{{ fa_num($stats['all']) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-send" label="کل ارسال‌ها" value="{{ fa_num($stats['submissions']) }}" color="info"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-hourglass-split" label="در انتظار تصحیح" value="{{ fa_num($stats['pending']) }}" color="warning"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-check2-circle" label="تصحیح‌شده" value="{{ fa_num($stats['graded']) }}" color="success"/>
    </div>
</div>

<x-card title="فهرست تکالیف" icon="bi-list-ul"
        actions="<a href='{{ route('admin.assignments.create') }}' class='btn btn-sm btn-primary'><i class='bi bi-plus-lg'></i> تکلیف جدید</a>">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-5">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="جستجوی عنوان..." value="{{ request('q') }}">
        </div>
        <div class="col-md-4">
            <select name="course_id" class="form-select form-select-sm">
                <option value="">همه دوره‌ها</option>
                @foreach ($courses as $id => $title)
                    <option value="{{ $id }}" @if (request('course_id') == $id) selected @endif>{{ $title }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-grid">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i> فیلتر</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>عنوان</th><th>دوره</th><th>مهلت</th><th>حداکثر نمره</th><th>ارسال‌ها</th><th>پیوست</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($assignments as $assignment)
                <tr>
                    <td class="small fw-bold">{{ $assignment->title }}</td>
                    <td class="small">{{ $assignment->course?->title ?? 'عمومی' }}</td>
                    <td class="small text-muted {{ $assignment->due_at && $assignment->due_at->isPast() ? 'text-danger' : '' }}">
                        {{ $assignment->due_at ? fa_datetime($assignment->due_at) : '—' }}
                    </td>
                    <td class="small">{{ fa_num($assignment->max_score) }}</td>
                    <td class="small">{{ fa_num($assignment->submissions_count) }}</td>
                    <td class="small">{{ $assignment->media_id ? '<i class="bi bi-paperclip text-success"></i> دارد' : '—' }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.assignments.edit', $assignment) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('admin.assignments.destroy', $assignment) }}"
                                  onsubmit="return confirm('حذف تکلیف «{{ $assignment->title }}» و ارسال‌های آن مطمئن هستید؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty icon="bi-clipboard-check" title="تکلیفی ثبت نشده است" actionLabel="ایجاد تکلیف" actionUrl="{{ route('admin.assignments.create') }}"/></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>

{{ $assignments->links() }}
@endsection
