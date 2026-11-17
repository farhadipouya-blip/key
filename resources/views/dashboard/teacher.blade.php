@extends('layouts.app')

@section('title', 'داشبورد مدرس')
@section('page_title', 'پنل مدرس')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-4">
        <x-stat icon="bi-easel" label="دوره‌های تدریس" value="{{ fa_num($courses->count()) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-pencil-square" label="تکالیف در انتظار تصحیح" value="{{ fa_num($pendingSubmissions) }}" color="warning" url="{{ route_if('teacher.assignments') }}"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-camera-video" label="کلاس‌های پیش‌رو" value="{{ fa_num($classes->count()) }}" color="info"/>
    </div>
</div>

<x-card title="دوره‌های من" icon="bi-easel" bodyClass="py-0">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>دوره</th><th>جلسات</th><th>ثبت‌نام‌شده</th><th>وضعیت</th><th></th></tr></thead>
            <tbody>
            @forelse ($courses as $course)
                <tr>
                    <td class="fw-bold">{{ $course->title }}</td>
                    <td class="small">{{ fa_num($course->lessons()->count()) }}</td>
                    <td class="small">{{ fa_num($course->enrollments_count) }}</td>
                    <td>
                        <x-badge :status="$course->is_published ? 'success' : 'secondary'"
                                 :labels="['success'=>'انتشارشده','secondary'=>'پیش‌نویس']"/>
                    </td>
                    <td><a href="{{ route_if('academy.courses.show', $course) }}" class="btn btn-sm btn-outline-primary">مشاهده</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-4"><x-empty icon="bi-easel" title="دوره‌ای به شما تخصیص داده نشده"/></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>

<x-card title="کلاس‌های پیش‌رو" icon="bi-camera-video">
    @forelse ($classes as $class)
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <div>
                <div class="fw-bold small">{{ $class->title }}</div>
                <div class="text-muted" style="font-size:.75rem">{{ fa_datetime($class->starts_at) }} — پلتفرم {{ $class->platform }}</div>
            </div>
            <a href="{{ $class->host_url ?: $class->join_url }}" target="_blank" class="btn btn-sm btn-success">ورود به کلاس</a>
        </div>
    @empty
        <x-empty icon="bi-camera-video-off" title="کلاس پیش‌رویی ندارید"/>
    @endforelse
</x-card>
@endsection
