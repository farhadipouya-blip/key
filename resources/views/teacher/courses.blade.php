@extends('layouts.app')

@section('title', 'دوره‌های تدریس من')
@section('page_title', 'دوره‌های تدریس من')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-easel" label="دوره‌های من" value="{{ fa_num($stats['courses']) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-people" label="دانشجویان" value="{{ fa_num($stats['students']) }}" color="success"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-clipboard-check" label="تکالیف" value="{{ fa_num($stats['assignments']) }}" color="warning" url="{{ route('teacher.assignments') }}"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-camera-video" label="کلاس‌های آنلاین" value="{{ fa_num($stats['classes']) }}" color="info" url="{{ route('teacher.classes') }}"/>
    </div>
</div>

<x-card title="دوره‌های اختصاص‌یافته به من" icon="bi-easel">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>عنوان</th><th>دسته</th><th>درس‌ها</th><th>دانشجو</th><th>قیمت</th><th>وضعیت</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($courses as $course)
                <tr>
                    <td class="small fw-bold">{{ $course->title }}</td>
                    <td class="small">{{ $course->category?->title ?? '—' }}</td>
                    <td class="small">{{ fa_num($course->lessons_count) }}</td>
                    <td class="small">{{ fa_num($course->enrollments_count) }}</td>
                    <td class="small">{{ $course->is_free ? 'رایگان' : money($course->price) }}</td>
                    <td>
                        <x-badge :status="$course->is_published ? 'success' : 'secondary'"
                                 :labels="['success' => 'منتشرشده', 'secondary' => 'پیش‌نویس']"/>
                    </td>
                    <td>
                        <a href="{{ route('teacher.gradebook') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-table"></i> دفتر نمره
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">
                    <x-empty icon="bi-easel" title="دوره‌ای به شما اختصاص داده نشده است"
                             message="مدیر سامانه دوره‌های شما را از بخش مدیریت دوره‌ها تعیین می‌کند."/>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
