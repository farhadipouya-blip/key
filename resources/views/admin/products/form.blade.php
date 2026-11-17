@extends('layouts.app')

@section('title', $product->exists ? 'ویرایش محصول' : 'محصول جدید')
@section('page_title', $product->exists ? 'ویرایش محصول' : 'محصول جدید')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="bi bi-box-seam text-primary"></i> {{ $product->exists ? 'ویرایش محصول «' . $product->title . '»' : 'ساخت محصول جدید' }}</h5>
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-right"></i> بازگشت به فهرست</a>
</div>

<x-card>
    <form method="POST"
          action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-md-7">
                <x-input name="title" label="عنوان محصول" required value="{{ old('title', $product->title) }}"
                         placeholder="مثلاً دفتر برنامه‌ریزی چاپی کلید"/>
            </div>
            <div class="col-md-5">
                <x-input name="slug" label="نامک (اختیاری)" value="{{ old('slug', $product->slug) }}"
                         placeholder="key-planner" help="خالی بگذارید تا خودکار ساخته شود؛ فقط حروف لاتین، عدد و خط تیره."/>
            </div>
            <div class="col-md-6">
                <x-input name="price" type="number" label="قیمت (تومان)" required money
                         value="{{ old('price', $product->price) }}"/>
            </div>
            <div class="col-md-6">
                <x-input name="cover_path" label="مسیر تصویر جلد (اختیاری)" value="{{ old('cover_path', $product->cover_path) }}"
                         placeholder="products/cover.jpg" help="مسیر فایل در دیسک عمومی؛ خالی بگذارید اگر تصویری ندارد."/>
            </div>
            <div class="col-12">
                <x-textarea name="description" label="توضیحات محصول" rows="5"
                            value="{{ old('description', $product->description) }}"/>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <x-switch name="is_published" label="منتشر شده باشد (نمایش در فروشگاه)" checked="{{ old('is_published', $product->is_published ? '1' : '') }}"/>
            </div>
        </div>

        <div class="d-flex gap-2 mt-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> {{ $product->exists ? 'ذخیره تغییرات' : 'ایجاد محصول' }}</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">انصراف</a>
        </div>
    </form>
</x-card>
@endsection
