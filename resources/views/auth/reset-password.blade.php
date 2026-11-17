@extends('layouts.auth')

@section('title', 'تعیین رمز جدید')

@section('content')
<h5 class="fw-bold mb-3">تعیین رمز عبور جدید</h5>
<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="mb-3">
        <label class="form-label">ایمیل</label>
        <input type="email" name="email" value="{{ old('email') }}" dir="ltr" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">رمز عبور جدید</label>
        <input type="password" name="password" class="form-control" required>
        @error('password') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label class="form-label">تکرار رمز جدید</label>
        <input type="password" name="password_confirmation" class="form-control" required>
    </div>
    <button class="btn btn-primary w-100" type="submit">تغییر رمز</button>
</form>
@endsection
