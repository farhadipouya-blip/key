@extends('layouts.auth')

@section('title', 'بازیابی رمز عبور')

@section('content')
<h5 class="fw-bold mb-3">بازیابی رمز عبور</h5>
<p class="text-muted small">ایمیل حساب خود را وارد کنید تا لینک بازیابی ارسال شود.</p>
<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="mb-3">
        <label class="form-label">ایمیل</label>
        <input type="email" name="email" value="{{ old('email') }}" dir="ltr" class="form-control" required>
        @error('email') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
    </div>
    <button class="btn btn-primary w-100" type="submit">ارسال لینک بازیابی</button>
</form>
<div class="text-center mt-3 small">
    <a href="{{ route('login') }}" class="text-decoration-none">بازگشت به ورود</a>
</div>
@endsection
