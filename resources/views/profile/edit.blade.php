@extends('layouts.app')

@section('title', 'پروفایل کاربری')
@section('page_title', 'پروفایل کاربری')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <x-card title="اطلاعات حساب" icon="bi-person">
            <div class="text-center mb-3">
                @if (auth()->user()->avatar_path)
                    <img src="{{ storage_public_url(auth()->user()->avatar_path) }}" class="rounded-circle" style="width:80px;height:80px;object-fit:cover" alt="avatar">
                @else
                    <span class="avatar mx-auto" style="width:80px;height:80px;font-size:1.8rem">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                @endif
            </div>
            <ul class="list-unstyled small mb-0">
                <li class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">نقش‌ها</span><span>{{ auth()->user()->roles->pluck('name')->implode('، ') }}</span></li>
                <li class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">آخرین ورود</span><span>{{ fa_datetime(auth()->user()->last_login_at) }}</span></li>
                <li class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">وضعیت اشتراک</span>
                    <span>{{ auth()->user()->hasActiveSubscription() ? '<span class="badge badge-soft-success">فعال</span>' : '<span class="badge badge-soft-secondary">بدون اشتراک</span>' }}</span>
                </li>
                <li class="d-flex justify-content-between py-1"><span class="text-muted">کیف پول</span><span>{{ money(auth()->user()->walletBalance()) }}</span></li>
            </ul>
        </x-card>
    </div>

    <div class="col-lg-8">
        <x-card title="ویرایش اطلاعات" icon="bi-pencil">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6"><x-input name="name" label="نام و نام خانوادگی" value="{{ old('name', auth()->user()->name) }}" required/></div>
                    <div class="col-md-6"><x-input name="phone" label="شماره موبایل" value="{{ old('phone', auth()->user()->phone) }}" required/></div>
                    <div class="col-md-6"><x-input name="email" label="ایمیل" type="email" value="{{ old('email', auth()->user()->email) }}"/></div>
                    <div class="col-md-6"><x-input name="city" label="شهر" value="{{ old('city', auth()->user()->city) }}"/></div>
                    <div class="col-md-6">
                        <x-select name="field_group" label="گروه آزمایشی" :value="old('field_group', auth()->user()->field_group)" :options="['experimental'=>'تجربی','math'=>'ریاضی','humanities'=>'انسانی','arts'=>'هنر','language'=>'زبان']"/>
                    </div>
                    <div class="col-md-6">
                        <x-select name="grade" label="پایه تحصیلی" :value="old('grade', auth()->user()->grade)" :options="['دهم'=>'دهم','یازدهم'=>'یازدهم','دوازدهم'=>'دوازدهم','فارغ‌التحصیل'=>'فارغ‌التحصیل']"/>
                    </div>
                    <div class="col-md-6"><x-input name="national_code" label="کد ملی" value="{{ old('national_code', auth()->user()->national_code) }}"/></div>
                    <div class="col-md-6">
                        <label class="form-label">تصویر پروفایل</label>
                        <input type="file" name="avatar" class="form-control" accept="image/*">
                    </div>
                    <div class="col-12"><x-textarea name="bio" label="درباره من" :value="old('bio', auth()->user()->bio)"/></div>
                </div>
                <button class="btn btn-primary">ذخیره تغییرات</button>
            </form>
        </x-card>

        <x-card title="تغییر رمز عبور" icon="bi-key">
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-4"><x-input name="current_password" label="رمز فعلی" type="password" required/></div>
                    <div class="col-md-4"><x-input name="password" label="رمز جدید" type="password" required/></div>
                    <div class="col-md-4"><x-input name="password_confirmation" label="تکرار رمز جدید" type="password" required/></div>
                </div>
                <button class="btn btn-outline-primary">تغییر رمز</button>
            </form>
        </x-card>
    </div>
</div>
@endsection
