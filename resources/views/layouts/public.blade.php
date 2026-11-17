<!DOCTYPE html>
<html lang="fa" dir="rtl" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'کلید') | کلید — سامانه جامع آموزشی</title>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/key.css') }}">
</head>
<body>
<div class="public-navbar py-2 sticky-top">
    <div class="container d-flex align-items-center gap-3">
        <a href="{{ route('public.home') }}" class="d-flex align-items-center gap-2 text-decoration-none">
            <span class="logo" style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#2f5bea,#7c3aed);display:grid;place-items:center;color:#fff;font-weight:800">K</span>
            <span class="fw-bold fs-5">کلید</span>
        </a>
        <div class="flex-grow-1"></div>
        @if (Route::has('articles.public.index'))
            <a href="{{ route_if('articles.public.index') }}" class="btn btn-link text-decoration-none">مقالات</a>
        @endif
        @if (Route::has('shop.index'))
            <a href="{{ route_if('shop.index') }}" class="btn btn-link text-decoration-none">فروشگاه</a>
        @endif
        <a href="{{ route('verify.certificate.form') }}" class="btn btn-link text-decoration-none">استعلام گواهی</a>
        <div class="vr"></div>
        @if (auth()->check())
            <a href="{{ auth()->user()->dashboardRoute() }}" class="btn btn-primary btn-sm">داشبورد من</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">ورود</a>
            <a href="{{ route('register') }}" class="btn btn-primary btn-sm">ثبت‌نام</a>
        @endif
    </div>
</div>

<main class="container py-4">
    @include('components.alerts')
    @yield('content')
</main>

<footer class="border-top py-4 mt-5 text-center text-muted small">
    <div class="container">
        سامانه کلید (KEY) — پلتفرم جامع آموزش، آزمون، مشاوره و خدمات آموزشی
    </div>
</footer>

<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/key.js') }}"></script>
@stack('scripts')
</body>
</html>
