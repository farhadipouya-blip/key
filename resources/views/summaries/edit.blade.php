@extends('layouts.app')

@section('title', 'ویرایش خلاصه')
@section('page_title', 'ویرایش خلاصه')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <x-card title="ویرایش: {{ $summary->title }}" icon="bi-pencil">
            <form method="POST" action="{{ route('summaries.update', $summary) }}">
                @csrf
                @method('PUT')
                <x-input name="title" label="عنوان" required :value="$summary->title"/>
                <x-textarea name="content" label="متن خلاصه" rows="10" required :value="$summary->content"/>
                <div class="row">
                    <div class="col-md-4">
                        <x-select name="category" label="دسته" :options="['خلاصه'=>'خلاصه','نکته'=>'نکته','فرمول'=>'فرمول','خط‌کشی'=>'خط‌کشی']" :value="$summary->category"/>
                    </div>
                    <div class="col-md-5">
                        <x-select name="subject_id" label="درس/مبحث" :options="$subjects->mapWithKeys(fn($s) => [$s->id => $s->fullTitle()])" :value="$summary->subject_id"/>
                    </div>
                    <div class="col-md-3">
                        <x-input name="tags" label="برچسب‌ها" :value="implode('،', $summary->tags ?? [])"/>
                    </div>
                </div>
                @if ($canPublish)
                    <x-switch name="is_public" label="انتشار عمومی" :checked="$summary->is_public"/>
                @endif
                <button class="btn btn-primary"><i class="bi bi-save"></i> به‌روزرسانی</button>
                <a href="{{ route('summaries.show', $summary) }}" class="btn btn-outline-secondary">انصراف</a>
            </form>
        </x-card>
    </div>
</div>
@endsection
