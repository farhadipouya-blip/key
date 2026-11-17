@extends('layouts.public')

@section('title', 'صفحه اصلی')

@section('content')
<div class="public-hero p-5 mb-4">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <h1 class="fw-bold mb-3">کلید — گنجینه کامل آمادگی برای کنکور و یادگیری</h1>
            <p class="mb-4" style="opacity:.92">
                بانک تست هوشمند، آزمون‌ساز آنلاین، مرور فاصله‌دار، برنامه‌ریزی مطالعه، آکادمی آموزشی،
                مشاوره حرفه‌ای، تخمین رتبه و انتخاب رشته — همه در یک سامانه یکپارچه.
            </p>
            <div class="d-flex gap-2 flex-wrap">
                @if (!auth()->check())
                    <a href="{{ route('register') }}" class="btn btn-light fw-bold px-4">شروع رایگان</a>
                    <a href="{{ route('login') }}" class="btn btn-outline-light px-4">ورود به حساب</a>
                @else
                    <a href="{{ auth()->user()->dashboardRoute() }}" class="btn btn-light fw-bold px-4">رفتن به داشبورد</a>
                @endif
            </div>
        </div>
        <div class="col-lg-4 text-center d-none d-lg-block">
            <i class="bi bi-key-fill" style="font-size:7rem; opacity:.85"></i>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    @foreach ([
        ['bi-journal-check', 'بانک تست و آزمون', 'سؤالات طبقه‌بندی‌شده، آزمون‌ساز، شبیه‌ساز آزمون و کارنامه تحلیلی'],
        ['bi-arrow-repeat', 'مرور هوشمند', 'مرور فاصله‌دار با الگوریتم SM-2 برای ماندگاری واقعی مطالب'],
        ['bi-calendar2-week', 'برنامه‌ریزی مطالعه', 'برنامه روزانه و هفتگی با مقایسه برنامه با عملکرد واقعی'],
        ['bi-mortarboard', 'آکادمی آموزشی', 'دوره‌های ویدیویی، کلاس آنلاین و تکلیف با بازخورد مدرس'],
        ['bi-graph-up-arrow', 'تحلیل عملکرد', 'نقاط قوت و ضعف، سطح تسلط و پیشنهاد اقدامات اصلاحی'],
        ['bi-trophy', 'تخمین رتبه', 'تخمین رتبه و تحلیل شانس قبولی بر اساس حدنصاب سال‌های گذشته'],
    ] as $feature)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 p-4">
                <i class="bi {{ $feature[0] }} text-primary mb-3" style="font-size:1.8rem"></i>
                <h6 class="fw-bold">{{ $feature[1] }}</h6>
                <p class="text-muted small mb-0">{{ $feature[2] }}</p>
            </div>
        </div>
    @endforeach
</div>

@if ($courses->isNotEmpty())
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">دوره‌های آموزشی</h5>
        <a href="{{ route_if('shop.index') }}" class="btn btn-sm btn-outline-primary">همه دوره‌ها</a>
    </div>
    <div class="row g-3 mb-4">
        @foreach ($courses as $course)
            <div class="col-md-4">
                <div class="card h-100">
                    @if ($course->cover_path)
                        <img src="{{ storage_public_url($course->cover_path) }}" class="card-img-top" alt="{{ $course->title }}" style="height:150px;object-fit:cover">
                    @endif
                    <div class="card-body">
                        <h6 class="fw-bold">{{ $course->title }}</h6>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            @if ($course->is_free)
                                <span class="badge badge-soft-success">رایگان</span>
                            @else
                                <strong class="text-primary">{{ money($course->price) }}</strong>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@if ($latestArticles->isNotEmpty())
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">مقالات آموزشی</h5>
        <a href="{{ route_if('articles.public.index') }}" class="btn btn-sm btn-outline-primary">همه مقالات</a>
    </div>
    <div class="row g-3 mb-4">
        @foreach ($latestArticles as $article)
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="fw-bold">{{ $article->title }}</h6>
                        <p class="text-muted small mb-2">{{ \Illuminate\Support\Str::limit($article->excerpt ?? $article->content, 90) }}</p>
                        <a href="{{ route('articles.public.show', $article) }}" class="small text-decoration-none">ادامه مطلب ←</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
