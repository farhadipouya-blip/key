@extends('layouts.app')

@section('title', 'مدیریت درس و مبحث')
@section('page_title', 'مدیریت درس و مبحث')

@php
    $levels = ['root' => 'درس', 'chapter' => 'فصل', 'lesson' => 'مبحث'];
@endphp

@section('content')
<x-alerts/>

<div class="row g-3 mb-2">
    <div class="col-6 col-md-3"><x-stat icon="bi-book" label="درس‌ها" value="{{ fa_num($stats['courses']) }}" color="primary"/></div>
    <div class="col-6 col-md-3"><x-stat icon="bi-collection" label="فصل‌ها" value="{{ fa_num($stats['chapters']) }}" color="info"/></div>
    <div class="col-6 col-md-3"><x-stat icon="bi-diagram-3" label="مباحث" value="{{ fa_num($stats['lessons']) }}" color="secondary"/></div>
    <div class="col-6 col-md-3"><x-stat icon="bi-database" label="کل سؤالات بانک" value="{{ fa_num($stats['questions']) }}" color="success"/></div>
</div>

<div class="row g-3">
    {{-- درخت دروس --}}
    <div class="col-xl-7">
        <x-card title="درخت دروس (درس › فصل › مبحث)" icon="bi-diagram-3">
            @forelse ($roots as $root)
                <div class="border rounded-3 p-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge badge-soft-primary">{{ $levels[$root->level] }}</span>
                        <span class="fw-bold flex-grow-1">{{ $root->title }}</span>
                        <span class="badge badge-soft-secondary">{{ fa_num($root->questions_count) }} سؤال</span>
                        <a href="{{ route('admin.subjects.index', ['edit' => $root->id]) }}" class="btn btn-sm btn-outline-primary" title="ویرایش"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('admin.subjects.destroy', $root) }}"
                              onsubmit="return confirm('حذف «{{ $root->title }}» همه فصل‌ها، مباحث و سؤالات زیرمجموعه را نیز حذف می‌کند. مطمئن هستید؟')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                    @foreach ($root->children()->withCount('questions')->get() as $chapter)
                        <div class="ms-4 mt-1 ps-3 border-start border-2">
                            <div class="d-flex align-items-center gap-2 py-1">
                                <span class="badge badge-soft-info">{{ $levels[$chapter->level] }}</span>
                                <span class="flex-grow-1 small fw-bold">{{ $chapter->title }}</span>
                                <span class="badge badge-soft-secondary">{{ fa_num($chapter->questions_count) }}</span>
                                <a href="{{ route('admin.subjects.index', ['edit' => $chapter->id]) }}" class="btn btn-sm btn-link py-0"><i class="bi bi-pencil small"></i></a>
                                <form method="POST" action="{{ route('admin.subjects.destroy', $chapter) }}"
                                      onsubmit="return confirm('حذف «{{ $chapter->title }}» و زیرمجموعه‌ها؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-link text-danger py-0"><i class="bi bi-trash small"></i></button>
                                </form>
                            </div>
                            @foreach ($chapter->children()->withCount('questions')->get() as $lesson)
                                <div class="ms-4 ps-3 border-start d-flex align-items-center gap-2 py-1">
                                    <span class="badge badge-soft-secondary">{{ $levels[$lesson->level] }}</span>
                                    <span class="flex-grow-1 small">{{ $lesson->title }}</span>
                                    <span class="badge badge-soft-secondary">{{ fa_num($lesson->questions_count) }}</span>
                                    <a href="{{ route('admin.subjects.index', ['edit' => $lesson->id]) }}" class="btn btn-sm btn-link py-0"><i class="bi bi-pencil small"></i></a>
                                    <form method="POST" action="{{ route('admin.subjects.destroy', $lesson) }}"
                                          onsubmit="return confirm('حذف «{{ $lesson->title }}»؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-link text-danger py-0"><i class="bi bi-trash small"></i></button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @empty
                <x-empty icon="bi-diagram-3" title="هنوز درسی ثبت نشده است" message="با فرم روبه‌رو اولین درس را اضافه کنید."/>
            @endforelse
        </x-card>
    </div>

    {{-- فرم افزودن/ویرایش --}}
    <div class="col-xl-5">
        <x-card title="{{ $editing ? 'ویرایش: ' . $editing->title : 'افزودن درس / فصل / مبحث' }}" icon="{{ $editing ? 'bi-pencil-square' : 'bi-plus-circle' }}">
            <form method="POST"
                  action="{{ $editing ? route('admin.subjects.update', $editing) : route('admin.subjects.store') }}">
                @csrf
                @if ($editing) @method('PUT') @endif
                <x-select name="parent_id" label="والد (تعیین‌کننده نوع)" :options="$parents" :value="$editing?->parent_id"
                          placeholder="بدون والد — درس جدید" help="بدون والد = درس | والدِ درس = فصل | والدِ فصل = مبحث"/>
                <x-input name="title" label="عنوان" :value="old('title', $editing?->title)" required placeholder="مثال: ریاضی، حد و پیوستگی، تابع یکنواخت"/>
                <x-input name="order" label="ترتیب نمایش" type="number" :value="old('order', $editing?->order ?? 0)"/>
                <x-textarea name="description" label="توضیح (اختیاری)" :value="old('description', $editing?->description)" rows="3"/>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="fld-active"
                           @if ($editing ? $editing->is_active : true) checked @endif>
                    <label class="form-check-label" for="fld-active">فعال</label>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> ذخیره</button>
                    @if ($editing)
                        <a href="{{ route('admin.subjects.index') }}" class="btn btn-outline-secondary">انصراف</a>
                    @endif
                </div>
            </form>
        </x-card>

        <x-card title="راهنما" icon="bi-info-circle" bodyClass="small text-muted">
            <ul class="mb-0 ps-3">
                <li>نوع گره (درس/فصل/مبحث) به‌صورت خودکار از والد تعیین می‌شود؛ نیازی به انتخاب دستی نیست.</li>
                <li>سؤالات بانک سؤال به مباحث یا درس‌ها متصل می‌شوند و آزمون‌ساز از همین ساختار استفاده می‌کند.</li>
                <li>حذف یک درس، همه فصل‌ها، مباحث و سؤالات وابسته را حذف می‌کند (با احتیاط).</li>
            </ul>
        </x-card>
    </div>
</div>
@endsection
