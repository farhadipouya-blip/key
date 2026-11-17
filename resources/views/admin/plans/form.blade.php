@extends('layouts.app')

@section('title', $plan->exists ? 'ویرایش بسته اشتراک' : 'بسته اشتراک جدید')
@section('page_title', $plan->exists ? 'ویرایش بسته اشتراک' : 'بسته اشتراک جدید')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="bi bi-star text-primary"></i> {{ $plan->exists ? 'ویرایش بسته اشتراک' : 'ساخت بسته اشتراک جدید' }}</h5>
    <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-right"></i> بازگشت به فهرست</a>
</div>

<x-card>
    <form method="POST"
          action="{{ $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store') }}">
        @csrf
        @if ($plan->exists)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-md-7">
                <x-input name="title" label="عنوان بسته" required value="{{ old('title', $plan->title) }}"
                         placeholder="مثلاً اشتراک ماهانه"/>
            </div>
            <div class="col-md-5">
                <x-input name="slug" label="نامک (اختیاری)" value="{{ old('slug', $plan->slug) }}"
                         placeholder="monthly-plan" help="خالی بگذارید تا خودکار ساخته شود؛ فقط حروف لاتین، عدد و خط تیره."/>
            </div>
            <div class="col-md-6">
                <x-input name="price" type="number" label="قیمت (تومان)" required money
                         value="{{ old('price', $plan->price) }}" placeholder="مثلاً 290000"/>
            </div>
            <div class="col-md-3">
                <x-input name="duration_days" type="number" label="مدت اشتراک (روز)" required
                         value="{{ old('duration_days', $plan->duration_days) }}"/>
            </div>
            <div class="col-md-3">
                <x-input name="badge" label="برچسب ویژه (اختیاری)" value="{{ old('badge', $plan->badge) }}"
                         placeholder="مثلاً محبوب"/>
            </div>
            <div class="col-12">
                <x-textarea name="features_text" label="ویژگی‌های بسته" rows="5"
                            value="{{ old('features_text', $plan->exists ? implode(PHP_EOL, (array) $plan->features) : '') }}"
                            placeholder="هر ویژگی را در یک خط بنویسید؛ مثلاً:&#10;آزمون نامحدود&#10;گزارش عملکرد هفتگی"
                            help="هر خط یک ویژگی محسوب می‌شود."/>
            </div>
            <div class="col-12">
                <x-textarea name="description" label="توضیحات (اختیاری)" rows="3"
                            value="{{ old('description', $plan->description) }}"/>
            </div>
            <div class="col-md-6">
                <x-input name="sort" type="number" label="ترتیب نمایش" value="{{ old('sort', $plan->sort) }}"/>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <x-switch name="is_active" label="فعال باشد (قابل خرید در فروشگاه)" checked="{{ old('is_active', $plan->is_active ? '1' : '') }}"/>
            </div>
        </div>

        <div class="d-flex gap-2 mt-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> {{ $plan->exists ? 'ذخیره تغییرات' : 'ایجاد بسته' }}</button>
            <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">انصراف</a>
        </div>
    </form>
</x-card>
@endsection
