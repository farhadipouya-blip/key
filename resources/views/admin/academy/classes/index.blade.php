@extends('layouts.app')

@section('title', 'مدیریت کلاس‌های آنلاین')
@section('page_title', 'مدیریت کلاس‌های آنلاین')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-4">
        <x-stat icon="bi-camera-video" label="کل کلاس‌ها" value="{{ fa_num($stats['all']) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-calendar-plus" label="کلاس‌های پیش‌رو" value="{{ fa_num($stats['upcoming']) }}" color="success"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-person-check" label="کل حضورها" value="{{ fa_num($stats['attendances']) }}" color="info"/>
    </div>
</div>

<x-card title="فهرست کلاس‌های آنلاین" icon="bi-list-ul"
        actions="<a href='{{ route('admin.classes.create') }}' class='btn btn-sm btn-primary'><i class='bi bi-plus-lg'></i> کلاس جدید</a>">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="جستجوی عنوان..." value="{{ request('q') }}">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">همه وضعیت‌ها</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" @if (request('status') === $key) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="platform" class="form-select form-select-sm">
                <option value="">همه پلتفرم‌ها</option>
                @foreach ($platforms as $key => $label)
                    <option value="{{ $key }}" @if (request('platform') === $key) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i> فیلتر</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>عنوان</th><th>پلتفرم</th><th>دوره</th><th>مدرس</th><th>زمان برگزاری</th><th>مدت</th><th>حضور</th><th>وضعیت</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($classes as $class)
                <tr>
                    <td class="small fw-bold">{{ $class->title }}</td>
                    <td class="small">{{ $platforms[$class->platform] ?? $class->platform }}</td>
                    <td class="small">{{ $class->course?->title ?? '—' }}</td>
                    <td class="small">{{ $class->teacher?->name ?? '—' }}</td>
                    <td class="small text-muted">{{ $class->starts_at ? fa_datetime($class->starts_at) : '—' }}</td>
                    <td class="small">{{ $class->duration_minutes ? duration_human($class->duration_minutes) : '—' }}</td>
                    <td class="small">{{ fa_num($class->attendances_count) }}</td>
                    <td>
                        <x-badge :status="$class->status"
                                 :labels="['scheduled'=>'زمان‌بندی‌شده','live'=>'در حال برگزاری','finished'=>'برگزار شد','canceled'=>'لغو شد']"/>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            @if ($class->join_url)
                                <a href="{{ $class->join_url }}" target="_blank" class="btn btn-sm btn-outline-success" title="لینک ورود"><i class="bi bi-box-arrow-in-right"></i></a>
                            @endif
                            <a href="{{ route('admin.classes.edit', $class) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('admin.classes.destroy', $class) }}"
                                  onsubmit="return confirm('حذف کلاس «{{ $class->title }}» مطمئن هستید؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9"><x-empty icon="bi-camera-video" title="کلاسی ثبت نشده است" actionLabel="ایجاد کلاس" actionUrl="{{ route('admin.classes.create') }}"/></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>

{{ $classes->links() }}
@endsection
