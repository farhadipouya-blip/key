@extends('layouts.app')

@section('title', $article->exists ? 'ویرایش مقاله' : 'مقاله جدید')
@section('page_title', $article->exists ? 'ویرایش مقاله: ' . $article->title : 'ایجاد مقاله')

@section('content')
<x-card title="{{ $article->exists ? 'ویرایش مقاله' : 'ایجاد مقاله جدید' }}" icon="bi-newspaper">
    <form method="POST" action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($article->exists) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-8">
                <x-input name="title" label="عنوان مقاله" required :value="old('title', $article->title ?? '')"/>
            </div>
            <div class="col-md-4">
                <x-input name="slug" label="نامک (slug)" :value="old('slug', $article->slug ?? '')"
                         help="خالی بگذارید تا از عنوان ساخته شود" dir="ltr"/>
            </div>
            <div class="col-12">
                <x-textarea name="excerpt" label="خلاصه" rows="2" maxlength="500"
                            help="حداکثر ۵۰۰ نویسه — در فهرست و کارت مقاله نمایش داده می‌شود"
                            :value="old('excerpt', $article->excerpt ?? '')"/>
            </div>
            <div class="col-12">
                <x-textarea name="content" label="متن کامل مقاله" rows="14" required
                            help="برای سطر جدید از خط خالی استفاده کنید؛ پاراگراف‌ها خودکار جدا می‌شوند"
                            :value="old('content', $article->content ?? '')"/>
            </div>
            <div class="col-md-6">
                <x-select name="category_id" label="دسته‌بندی" :options="$categories" :value="old('category_id', $article->category_id ?? '')"/>
            </div>
            <div class="col-md-3">
                <x-select name="status" label="وضعیت" required :options="['draft' => 'پیش‌نویس', 'published' => 'منتشرشده']" :value="old('status', $article->status ?? 'draft')"/>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <x-switch name="is_public" label="نمایش عمومی (بدون ورود)" :checked="old('is_public', $article->is_public ?? true)"/>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label" for="fld-published_at">تاریخ و ساعت انتشار</label>
                    <input type="datetime-local" id="fld-published_at" name="published_at" class="form-control"
                           value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i') ?? '') }}"
                           placeholder="خالی = زمان انتشار خودکار">
                    @error('published_at')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label" for="fld-cover">تصویر جلد</label>
                    <input type="file" id="fld-cover" name="cover" class="form-control" accept="image/*">
                    <div class="form-text">jpg/png/webp — حداکثر ۴ مگابایت</div>
                    @error('cover')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                    @if ($article->cover_path)
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <img src="{{ storage_public_url($article->cover_path) }}" alt="جلد فعلی" class="img-thumbnail" style="max-height:100px">
                            <span class="small text-muted">جلد فعلی</span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-md-6">
                <x-input name="meta_title" label="عنوان سئو" :value="old('meta_title', $article->meta_title ?? '')"
                         help="عنوانی که در نتایج جستجو نمایش داده می‌شود"/>
            </div>
            <div class="col-md-6">
                <x-textarea name="meta_description" label="توضیح سئو" rows="2" maxlength="300" :value="old('meta_description', $article->meta_description ?? '')"/>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> {{ $article->exists ? 'ذخیره تغییرات' : 'ایجاد مقاله' }}</button>
                <a href="{{ route('admin.articles.index') }}" class="btn btn-outline-secondary">انصراف</a>
            </div>
        </div>
    </form>
</x-card>
@endsection
