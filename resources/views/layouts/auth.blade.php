<!DOCTYPE html>
<html lang="fa" dir="rtl" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ورود') | کلید</title>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/key.css') }}">
</head>
<body class="d-flex align-items-center" style="min-height:100vh; background: linear-gradient(135deg, #eef2ff 0%, #fdf6ec 100%);">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="text-center mb-4">
                <div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#2f5bea,#7c3aed);display:grid;place-items:center;color:#fff;font-weight:800;font-size:1.5rem;margin:0 auto">K</div>
                <h4 class="mt-3 fw-bold">کلید</h4>
                <p class="text-muted small">سامانه جامع مدیریت آموزش، آزمون و مشاوره</p>
            </div>
            <div class="card p-4">
                @include('components.alerts')
                @yield('content')
            </div>
            <p class="text-center text-muted small mt-3">© {{ fa_date(now()) }} — سامانه کلید</p>
        </div>
    </div>
</div>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/key.js') }}"></script>
</body>
</html>
