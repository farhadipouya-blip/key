@extends('layouts.app')

@section('title', 'ثبت تیکت جدید')
@section('page_title', 'ثبت تیکت جدید')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-life-preserver text-primary"></i> ثبت تیکت پشتیبانی</h5>
            <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-right"></i> بازگشت به تیکت‌ها
            </a>
        </div>

        <x-card title="مشخصات درخواست" icon="bi-pencil-square">
            <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
                @csrf
                <x-input name="subject" label="موضوع تیکت" required placeholder="خلاصه‌ای کوتاه از درخواست خود..." :value="old('subject')"/>

                <div class="row">
                    <div class="col-md-6">
                        <x-select name="category" label="دسته‌بندی" required
                                  :options="array_combine($categories, $categories)"
                                  :value="old('category')"
                                  help="موضوع درخواست در کدام دسته قرار می‌گیرد؟"/>
                    </div>
                    <div class="col-md-6">
                        <x-select name="priority" label="اولویت" required
                                  :options="$priorities"
                                  :value="old('priority', 'normal')"
                                  help="اولویت «فوری» فقط برای مسائل مسدودکننده استفاده شود."/>
                    </div>
                </div>

                <x-textarea name="body" label="متن تیکت" required :rows="8"
                            placeholder="شرح کامل درخواست یا مشکل را بنویسید..."
                            :value="old('body')"
                            help="حداکثر ۱۰٬۰۰۰ کاراکتر"/>

                <div class="mb-3">
                    <label class="form-label" for="fld-attachment">پیوست (اختیاری)</label>
                    <input type="file" id="fld-attachment" name="attachment"
                           class="form-control @error('attachment') is-invalid @enderror">
                    <div class="form-text">حجم فایل حداکثر ۱۰ مگابایت — تصویر، سند یا فایل مربوط به مشکل.</div>
                    @error('attachment')
                        <div class="small text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i> ارسال تیکت
                    </button>
                    <a href="{{ route('tickets.index') }}" class="btn btn-light">انصراف</a>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
