@extends('layouts.app')

@section('title', 'مدیریت دوره‌ها')
@section('page_title', 'مدیریت دوره‌ها')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-mortarboard" label="کل دوره‌ها" value="{{ fa_num($stats['all']) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-eye" label="منتشرشده" value="{{ fa_num($stats['published']) }}" color="success"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-cash-coin" label="دوره‌های پولی" value="{{ fa_num($stats['paid']) }}" color="warning"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-collection-play" label="کل درس‌ها" value="{{ fa_num($stats['lessons']) }}" color="info"/>
    </div>
</div>

<x-card title="فهرست دوره‌ها" icon="bi-list-ul"
        actions="<a href='{{ route('admin.courses.create') }}' class='btn btn-sm btn-primary'><i class='bi bi-plus-lg'></i> دوره جدید</a>">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="جستجوی عنوان..." value="{{ request('q') }}">
        </div>
        <div class="col-md-3">
            <select name="category_id" class="form-select form-select-sm">
                <option value="">همه دسته‌ها</option>
                @foreach ($categories as $id => $title)
                    <option value="{{ $id }}" @if (request('category_id') == $id) selected @endif>{{ $title }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">همه وضعیت‌ها</option>
                <option value="published" @if (request('status') === 'published') selected @endif>منتشرشده</option>
                <option value="draft" @if (request('status') === 'draft') selected @endif>پیش‌نویس</option>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i> فیلتر</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>عنوان</th>
                    <th>دسته</th>
                    <th>مدرس</th>
                    <th>قیمت</th>
                    <th>درس‌ها</th>
                    <th>دانشجو</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($courses as $course)
                <tr>
                    <td class="fw-bold small">
                        {{ $course->title }}
                        @if ($course->cover_path)
                            <i class="bi bi-image text-muted small" title="تصویر جلد دارد"></i>
                        @endif
                    </td>
                    <td class="small">{{ $course->category?->title ?? '—' }}</td>
                    <td class="small">{{ $course->teacher?->name ?? '—' }}</td>
                    <td class="small">{{ $course->is_free ? 'رایگان' : money($course->price) }}</td>
                    <td class="small">{{ fa_num($course->lessons_count) }}</td>
                    <td class="small">{{ fa_num($course->enrollments_count) }}</td>
                    <td>
                        <x-badge :status="$course->is_published ? 'success' : 'secondary'"
                                 :labels="['success' => 'منتشرشده', 'secondary' => 'پیش‌نویس']"/>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-sm btn-outline-primary" title="ویرایش و مدیریت درس‌ها"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('admin.courses.destroy', $course) }}"
                                  onsubmit="return confirm('حذف دوره «{{ $course->title }}» و همه درس‌های آن، مطمئن هستید؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><x-empty icon="bi-mortarboard" title="دوره‌ای ثبت نشده است" message="اولین دوره خود را ایجاد کنید." actionLabel="ایجاد دوره" actionUrl="{{ route('admin.courses.create') }}"/></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>

{{ $courses->links() }}
@endsection
