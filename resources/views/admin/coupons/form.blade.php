@extends('layouts.app')

@section('title', $coupon->exists ? 'ویرایش کوپن' : 'کوپن جدید')
@section('page_title', $coupon->exists ? 'ویرایش کوپن' : 'کوپن جدید')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="bi bi-ticket-perforated text-primary"></i> {{ $coupon->exists ? 'ویرایش کوپن «' . $coupon->code . '»' : 'ساخت کوپن تخفیف جدید' }}</h5>
    <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-right"></i> بازگشت به فهرست</a>
</div>

<x-card>
    <form method="POST"
          action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}">
        @csrf
        @if ($coupon->exists)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-md-4">
                <x-input name="code" label="کد کوپن" required value="{{ old('code', $coupon->code) }}"
                         placeholder="مثلاً KEY-50" help="حروف کوپن به‌صورت خودکار به حروف بزرگ تبدیل می‌شود."/>
            </div>
            <div class="col-md-4">
                <x-select name="type" label="نوع تخفیف" required
                          :options="['percent' => 'درصدی', 'fixed' => 'مبلغ ثابت (تومان)']"
                          :value="old('type', $coupon->type)"/>
            </div>
            <div class="col-md-4">
                <x-input name="value" type="number" label="مقدار تخفیف" required
                         value="{{ old('value', $coupon->value) }}"
                         help="اگر نوع تخفیف درصدی است، مقدار بین ۰ تا ۱۰۰ باشد."/>
            </div>
            <div class="col-md-4">
                <x-input name="min_amount" type="number" label="حداقل مبلغ سبد (اختیاری)" money
                         value="{{ old('min_amount', $coupon->min_amount) }}"
                         help="کوپن فقط برای سبد‌هایی با مبلغ بیش از این مقدار اعمال می‌شود."/>
            </div>
            <div class="col-md-4">
                <x-input name="max_uses" type="number" label="حداکثر تعداد استفاده (اختیاری)"
                         value="{{ old('max_uses', $coupon->max_uses) }}"/>
            </div>
            <div class="col-md-4">
                <x-input name="expires_at" type="date" label="تاریخ انقضا (اختیاری)"
                         value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d')) }}"/>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <x-switch name="is_active" label="فعال باشد" checked="{{ old('is_active', $coupon->is_active ? '1' : '') }}"/>
            </div>
        </div>

        <div class="d-flex gap-2 mt-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> {{ $coupon->exists ? 'ذخیره تغییرات' : 'ایجاد کوپن' }}</button>
            <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">انصراف</a>
        </div>
    </form>
</x-card>
@endsection
