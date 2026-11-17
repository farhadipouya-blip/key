@extends('layouts.auth')

@section('title', 'ثبت‌نام')

@section('content')
<h5 class="fw-bold mb-3">ساخت حساب دانشجویی</h5>

<form method="POST" action="{{ route('register.post') }}">
    @csrf
    <div class="mb-2">
        <label class="form-label">نام و نام خانوادگی <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
        @error('name') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
    </div>
    <div class="mb-2">
        <label class="form-label">شماره موبایل <span class="text-danger">*</span></label>
        <input type="tel" name="phone" value="{{ old('phone') }}" dir="ltr" class="form-control" placeholder="09xxxxxxxxx" required>
        @error('phone') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
    </div>
    <div class="mb-2">
        <label class="form-label">ایمیل (اختیاری)</label>
        <input type="email" name="email" value="{{ old('email') }}" dir="ltr" class="form-control">
        @error('email') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
    </div>
    <div class="row">
        <div class="col-6 mb-2">
            <label class="form-label">پایه تحصیلی</label>
            <select name="grade" class="form-select">
                <option value="">انتخاب</option>
                @foreach (['دهم', 'یازدهم', 'دوازدهم', 'فارغ‌التحصیل'] as $g)
                    <option value="{{ $g }}" @if(old('grade') == $g) selected @endif>{{ $g }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 mb-2">
            <label class="form-label">گروه آزمایشی</label>
            <select name="field_group" class="form-select">
                <option value="">انتخاب</option>
                @foreach (['تجربی' => 'experimental', 'ریاضی' => 'math', 'انسانی' => 'humanities', 'هنر' => 'arts', 'زبان' => 'language'] as $name => $slug)
                    <option value="{{ $slug }}" @if(old('field_group') == $slug) selected @endif>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="mb-2">
        <label class="form-label">رمز عبور <span class="text-danger">*</span></label>
        <input type="password" name="password" class="form-control" required>
        @error('password') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label class="form-label">تکرار رمز عبور <span class="text-danger">*</span></label>
        <input type="password" name="password_confirmation" class="form-control" required>
    </div>
    <button class="btn btn-primary w-100" type="submit">ثبت‌نام</button>
</form>

<div class="text-center mt-3 small">
    حساب دارید؟ <a href="{{ route('login') }}" class="text-decoration-none">ورود</a>
</div>
@endsection
