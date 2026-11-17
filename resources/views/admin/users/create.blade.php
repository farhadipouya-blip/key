@extends('layouts.app')

@section('title', 'کاربر جدید')
@section('page_title', 'ساخت کاربر جدید')

@php
    $fieldGroups = [
        'math' => 'ریاضی و فیزیک',
        'science' => 'علوم تجربی',
        'humanities' => 'علوم انسانی',
        'language' => 'زبان‌های خارجی',
        'arts' => 'هنر',
    ];
@endphp

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <x-card title="اطلاعات کاربر" icon="bi-person-plus">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <x-input name="name" label="نام و نام خانوادگی" required value="{{ old('name') }}"/>
                    </div>
                    <div class="col-md-6">
                        <x-input name="email" type="email" label="ایمیل" required value="{{ old('email') }}"/>
                    </div>
                    <div class="col-md-6">
                        <x-input name="phone" label="شماره موبایل" value="{{ old('phone') }}" placeholder="۰۹xxxxxxxxx"/>
                    </div>
                    <div class="col-md-6">
                        <x-input name="password" type="password" label="رمز عبور" required help="حداقل ۸ کاراکتر"/>
                    </div>
                    <div class="col-md-6">
                        <x-input name="grade" label="پایه تحصیلی" value="{{ old('grade') }}" placeholder="مثلاً دوازدهم"/>
                    </div>
                    <div class="col-md-6">
                        <x-select name="field_group" label="رشته تحصیلی" :options="$fieldGroups" :value="old('field_group')"/>
                    </div>
                </div>
                <x-select name="role_id" label="نقش کاربر" :options="$roles->mapWithKeys(fn ($r) => [$r->id => $r->name])" :value="old('role_id')" required/>
                <x-switch name="is_active" label="حساب فعال باشد" checked="{{ old('is_active', true) }}"/>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> ذخیره کاربر</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">انصراف</a>
                </div>
            </form>
        </x-card>
    </div>
    <div class="col-lg-5">
        <x-card title="راهنما" icon="bi-info-circle">
            <ul class="small text-muted mb-0 ps-3">
                <li>ایمیل و شماره موبایل باید یکتا باشند.</li>
                <li>کاربر با نقش انتخابی ساخته می‌شود؛ بعداً از صفحه ویرایش می‌توانید نقش‌ها را تغییر دهید.</li>
                <li>غیرفعال کردن حساب مانع ورود کاربر می‌شود اما داده‌های او حفظ می‌شود.</li>
                <li>برای تغییر نقش‌های چندگانه از دکمه ویرایش استفاده کنید.</li>
            </ul>
        </x-card>
    </div>
</div>
@endsection
