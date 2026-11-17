@extends('layouts.app')

@section('title', 'آکادمی آموزشی')
@section('page_title', 'آکادمی آموزشی — دوره‌ها')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-4">
        <x-stat icon="bi-mortarboard" label="دوره‌های منتشرشده" value="{{ fa_num($stats['all']) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-gift" label="دوره‌های رایگان" value="{{ fa_num($stats['free']) }}" color="success"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-book" label="دوره‌های من" value="{{ fa_num($stats['mine']) }}" color="info" url="{{ route('academy.my-courses') }}"/>
    </div>
</div>

<x-card title="فیلتر دوره‌ها" icon="bi-funnel" bodyClass="py-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label" for="q">جستجو</label>
            <input type="text" id="q" name="q" class="form-control" placeholder="عنوان یا توضیحات دوره..." value="{{ request('q') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="category_id">دسته‌بندی</label>
            <select id="category_id" name="category_id" class="form-select">
                <option value="">همه دسته‌ها</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @if (request('category_id') == $category->id) selected @endif>{{ $category->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="type">نوع</label>
            <select id="type" name="type" class="form-select">
                <option value="">همه</option>
                <option value="free" @if (request('type') === 'free') selected @endif>رایگان</option>
                <option value="paid" @if (request('type') === 'paid') selected @endif>پولی</option>
            </select>
        </div>
        <div class="col-md-1 d-grid">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
        </div>
    </form>
</x-card>

<h5 class="fw-bold mb-3">دوره‌های آموزشی</h5>
<div class="row g-3">
    @forelse ($courses as $course)
        @php
            $isEnrolled = in_array($course->id, $enrolledIds);
        @endphp
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                @if ($course->cover_path)
                    <a href="{{ route('academy.courses.show', $course) }}">
                        <img src="{{ storage_public_url($course->cover_path) }}" class="card-img-top" alt="{{ $course->title }}" style="height:160px;object-fit:cover">
                    </a>
                @else
                    <a href="{{ route('academy.courses.show', $course) }}" class="d-block text-center py-4 bg-light">
                        <i class="bi bi-mortarboard text-primary" style="font-size:3rem"></i>
                    </a>
                @endif
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <h6 class="fw-bold mb-0">
                            <a href="{{ route('academy.courses.show', $course) }}" class="text-decoration-none text-body">{{ $course->title }}</a>
                        </h6>
                        @if ($course->is_free)
                            <span class="badge badge-soft-success">رایگان</span>
                        @else
                            <span class="badge badge-soft-warning">پولی</span>
                        @endif
                    </div>
                    @if ($course->category)
                        <span class="badge badge-soft-info align-self-start mb-2">{{ $course->category->title }}</span>
                    @endif
                    <p class="text-muted small mb-2">{{ \Illuminate\Support\Str::limit($course->description, 110) ?: 'بدون توضیح' }}</p>
                    <div class="d-flex flex-wrap gap-3 small text-muted mb-3 mt-auto">
                        @if ($course->teacher)
                            <span><i class="bi bi-person"></i> {{ $course->teacher->name }}</span>
                        @endif
                        <span><i class="bi bi-collection-play"></i> {{ fa_num($course->lessons_count) }} درس</span>
                        <span><i class="bi bi-people"></i> {{ fa_num($course->enrollments_count) }} دانشجو</span>
                        @if ($course->duration_hours)
                            <span><i class="bi bi-clock"></i> {{ duration_human($course->duration_hours * 60) }}</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        @if ($course->is_free)
                            <span class="fw-bold text-success">رایگان</span>
                        @else
                            <span class="fw-bold text-primary">{{ money($course->price) }}</span>
                        @endif
                        @if ($isEnrolled)
                            <a href="{{ route('academy.courses.show', $course) }}" class="btn btn-success btn-sm"><i class="bi bi-check2-circle"></i> ثبت‌نام‌شده — ادامه</a>
                        @elseif ($course->is_free)
                            <form method="POST" action="{{ route('academy.enroll', $course) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> ثبت‌نام رایگان</button>
                            </form>
                        @else
                            <a href="{{ route('academy.courses.show', $course) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye"></i> جزئیات و خرید</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <x-empty icon="bi-mortarboard" title="دوره‌ای یافت نشد" message="با تغییر فیلترها جستجو کنید یا بعداً مراجعه نمایید."/>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $courses->links() }}
</div>
@endsection
