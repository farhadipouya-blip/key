@extends('layouts.auth')

@section('title', 'ورود به حساب')

@section('content')
<h5 class="fw-bold mb-3">ورود به حساب کاربری</h5>

<form method="POST" action="{{ route('login.post') }}">
    @csrf
    <div class="mb-3">
        <label class="form-label">ایمیل یا شماره موبایل</label>
        <input type="text" name="identifier" value="{{ old('identifier') }}" class="form-control" required autofocus>
        @error('identifier') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label class="form-label">رمز عبور</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" name="remember" id="remember" value="1">
        <label class="form-check-label small" for="remember">مرا به خاطر بسپار</label>
    </div>
    <button class="btn btn-primary w-100" type="submit">ورود</button>
</form>

<div class="text-center mt-3 small">
    <a href="{{ route('password.request') }}" class="text-decoration-none">بازیابی رمز عبور</a>
    <span class="mx-2 text-muted">|</span>
    <a href="{{ route('register') }}" class="text-decoration-none">ثبت‌نام جدید</a>
</div>
@endsection
