@extends('layouts.app')

@section('title', 'ساخت خلاصه جدید')
@section('page_title', 'ساخت خلاصه جدید')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <x-card title="خلاصه / نکته جدید" icon="bi-file-earmark-text">
            <form method="POST" action="{{ route('summaries.store') }}">
                @csrf
                <x-input name="title" label="عنوان" required placeholder="مثلاً خلاصه فصل ماتریس"/>
                <x-textarea name="content" label="متن خلاصه" rows="10" required placeholder="محتوای خلاصه یا نکته را بنویسید..."/>
                <div class="row">
                    <div class="col-md-4">
                        <x-select name="category" label="دسته" :options="['خلاصه'=>'خلاصه','نکته'=>'نکته','فرمول'=>'فرمول','خط‌کشی'=>'خط‌کشی']" value="{{ old('category') }}"/>
                    </div>
                    <div class="col-md-5">
                        <x-select name="subject_id" label="درس/مبحث" :options="$subjects->mapWithKeys(fn($s) => [$s->id => $s->fullTitle()])"/>
                    </div>
                    <div class="col-md-3">
                        <x-input name="tags" label="برچسب‌ها" placeholder="با ویرگول جدا کنید"/>
                    </div>
                </div>
                @if ($canPublish)
                    <x-switch name="is_public" label="انتشار عمومی (برای همه کاربران)"/>
                @endif
                <button class="btn btn-primary"><i class="bi bi-save"></i> ذخیره خلاصه</button>
                <a href="{{ route('summaries.index') }}" class="btn btn-outline-secondary">انصراف</a>
            </form>
        </x-card>
    </div>
</div>
@endsection
