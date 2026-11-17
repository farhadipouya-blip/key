@extends('layouts.app')

@section('title', $assignment->exists ? 'ویرایش تکلیف' : 'تکلیف جدید')
@section('page_title', $assignment->exists ? 'ویرایش تکلیف: ' . $assignment->title : 'ایجاد تکلیف')

@section('content')
<x-card title="{{ $assignment->exists ? 'ویرایش تکلیف' : 'ایجاد تکلیف جدید' }}" icon="bi-clipboard-check">
    <form method="POST" action="{{ $assignment->exists ? route('admin.assignments.update', $assignment) : route('admin.assignments.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($assignment->exists) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-8">
                <x-input name="title" label="عنوان تکلیف" required :value="old('title', $assignment->title ?? '')"/>
            </div>
            <div class="col-md-4">
                <x-input name="max_score" label="حداکثر نمره" type="number" :value="old('max_score', $assignment->max_score ?? 100)"/>
            </div>
            <div class="col-12">
                <x-textarea name="description" label="شرح تکلیف" rows="6" required
                            help="شرح کامل تکلیف، الزامات و نحوه ارسال را بنویسید"
                            :value="old('description', $assignment->description ?? '')"/>
            </div>
            <div class="col-md-6">
                <x-select name="course_id" label="دوره مرتبط" :options="$courses" :value="old('course_id', $assignment->course_id ?? '')"
                          help="تکلیف بدون دوره به‌صورت عمومی تعریف می‌شود"/>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label" for="fld-due_at">مهلت تحویل</label>
                    <input type="datetime-local" id="fld-due_at" name="due_at" class="form-control"
                           value="{{ old('due_at', $assignment->due_at?->format('Y-m-d\TH:i') ?? '') }}">
                    @error('due_at')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label" for="fld-attachment">فایل پیوست تکلیف (اختیاری)</label>
                    <input type="file" id="fld-attachment" name="attachment" class="form-control">
                    <div class="form-text">pdf/word/zip/تصویر/متن/صوت/ویدیو — حداکثر ۲۰ مگابایت</div>
                    @error('attachment')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                    @if (isset($attachment) && $attachment)
                        <div class="small text-muted mt-2 border rounded p-2">
                            <i class="bi bi-paperclip"></i> پیوست فعلی:
                            <a href="{{ storage_public_url($attachment->path) }}" target="_blank" class="text-decoration-none">{{ $attachment->name }}</a>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_attachment" value="1" id="fld-remove_attachment">
                                <label class="form-check-label small" for="fld-remove_attachment">حذف پیوست</label>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> {{ $assignment->exists ? 'ذخیره تغییرات' : 'ایجاد تکلیف' }}</button>
                <a href="{{ route('admin.assignments.index') }}" class="btn btn-outline-secondary">انصراف</a>
            </div>
        </div>
    </form>
</x-card>
@endsection
