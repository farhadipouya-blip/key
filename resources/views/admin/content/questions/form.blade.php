@extends('layouts.app')

@section('title', $question->exists ? 'ویرایش سؤال' : 'سؤال جدید')
@section('page_title', $question->exists ? 'ویرایش سؤال #' . fa_num($question->id) : 'ثبت سؤال جدید')

@push('scripts')
<script src="{{ asset('assets/js/tex-svg.js') }}"></script>
@endpush

@section('content')
<x-alerts/>

<div class="row g-3">
    <div class="col-xl-8">
        <x-card title="مشخصات سؤال" icon="bi-ui-checks">
            <form method="POST" enctype="multipart/form-data"
                  action="{{ $question->exists ? route('admin.questions.update', $question) : route('admin.questions.store') }}">
                @csrf
                @if ($question->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <x-select name="subject_id" label="درس / مبحث" :options="$subjects" :value="old('subject_id', $question->subject_id)" required/>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">نوع سؤال <span class="text-danger">*</span></label>
                        <select name="type" id="fld-type" class="form-select">
                            <option value="multi" @if (old('type', $question->type) === 'multi') selected @endif>تستی (چهارگزینه‌ای)</option>
                            <option value="descriptive" @if (old('type', $question->type) === 'descriptive') selected @endif>تشریحی</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">سطح دشواری <span class="text-danger">*</span></label>
                        <select name="difficulty" class="form-select">
                            <option value="easy" @if (old('difficulty', $question->difficulty) === 'easy') selected @endif>آسان</option>
                            <option value="medium" @if (old('difficulty', $question->difficulty) === 'medium') selected @endif>متوسط</option>
                            <option value="hard" @if (old('difficulty', $question->difficulty) === 'hard') selected @endif>دشوار</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <x-textarea name="text" label="متن سؤال" :value="old('text', $question->text)" rows="5" required
                                help="فرمول ریاضی با $...$ یا $$...$$ (فرمت LaTeX) — پیش‌نمایش زیر فرم نشان می‌دهد."/>
                    <div class="card bg-body-tertiary border-0 mb-3" id="question-preview" dir="rtl">
                        <div class="card-body py-2 small" id="question-preview-body">پیش‌نمایش فرمول‌ها اینجا نمایش داده می‌شود.</div>
                    </div>
                </div>

                {{-- گزینه‌های تستی --}}
                <div id="options-block">
                    <label class="form-label fw-bold">گزینه‌ها <span class="text-danger">*</span></label>
                    <div class="small text-muted mb-2">گزینه صحیح را با انتخاب دایره روبه‌رو مشخص کنید.</div>
                    @php
                        $oldOptions = old('option_texts');
                        $oldCorrect = old('correct_option');
                    @endphp
                    @foreach ([1, 2, 3, 4] as $order)
                        @php
                            $existingOption = $question->options->firstWhere('order', $order);
                            $textValue = $oldOptions[$order] ?? $existingOption?->text ?? '';
                            $isChecked = $oldCorrect !== null ? ((int) $oldCorrect === $order) : ($existingOption?->is_correct ?? $order === 1);
                        @endphp
                        <div class="input-group input-group-sm mb-2 option-row">
                            <div class="input-group-text bg-transparent">
                                <input class="form-check-input mt-0" type="radio" name="correct_option" value="{{ $order }}" @if ($isChecked) checked @endif aria-label="گزینه صحیح {{ fa_num($order) }}">
                            </div>
                            <span class="input-group-text">{{ fa_num($order) }}</span>
                            <input type="text" class="form-control option-input" name="option_texts[{{ $order }}]" value="{{ $textValue }}" placeholder="متن گزینه {{ fa_num($order) }} (فرمول: $x^2$)">
                        </div>
                    @endforeach
                    @error('correct_option')<div class="small text-danger mb-2">{{ $message }}</div>@enderror
                    @error('option_texts')<div class="small text-danger mb-2">{{ $message }}</div>@enderror
                </div>

                {{-- پاسخ تشریحی --}}
                <div id="descriptive-block" class="d-none">
                    <x-textarea name="answer_text" label="پاسخ نمونه تشریحی" :value="old('answer_text', $question->answer_text)" rows="3"/>
                </div>

                <x-textarea name="explanation" label="توضیح / نکته حل (نمایش در کارنامه)" :value="old('explanation', $question->explanation)" rows="3"/>
                <div class="row g-3">
                    <div class="col-md-3">
                        <x-input name="default_marks" label="بارم پیش‌فرض" type="number" step="0.25" min="0.25" :value="old('default_marks', $question->default_marks ?? 1)" required/>
                    </div>
                    <div class="col-md-4">
                        <x-input name="source" label="منبع (اختیاری)" :value="old('source', $question->source)" placeholder="مثال: کنکور سراسری ۱۴۰۳"/>
                    </div>
                    <div class="col-md-2">
                        <x-input name="year" label="سال طرح" type="number" :value="old('year', $question->year)"/>
                    </div>
                    <div class="col-md-3 d-flex align-items-end pb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="fld-active"
                                   @if ($question->exists ? $question->is_active : true) checked @endif>
                            <label class="form-check-label" for="fld-active">فعال در بانک</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="fld-image">تصویر پیوست (اختیاری)</label>
                    <input type="file" id="fld-image" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    <div class="form-text">jpg / png / webp — حداکثر ۴ مگابایت.</div>
                    @error('image')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                    @if ($question->image_path)
                        <div class="mt-2"><img src="{{ storage_public_url($question->image_path) }}" alt="تصویر سؤال" class="img-thumbnail" style="max-height:120px"></div>
                    @endif
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> ذخیره سؤال</button>
                    <a href="{{ route('admin.questions.index') }}" class="btn btn-outline-secondary">بازگشت به بانک</a>
                </div>
            </form>
        </x-card>
    </div>

    <div class="col-xl-4">
        <x-card title="راهنمای فرمول‌نویسی" icon="bi-math" bodyClass="small">
            <table class="table table-sm table-bordered">
                <tbody>
                    <tr><td dir="ltr" class="font-monospace">$x^2$</td><td>توان دوم</td></tr>
                    <tr><td dir="ltr" class="font-monospace">$\frac{a}{b}$</td><td>کسر</td></tr>
                    <tr><td dir="ltr" class="font-monospace">$\sqrt{x}$</td><td>جذر</td></tr>
                    <tr><td dir="ltr" class="font-monospace">$\int_0^1 f(x)dx$</td><td>انتگرال</td></tr>
                    <tr><td dir="ltr" class="font-monospace">$$...$$</td><td>فرمول در وسط خط</td></tr>
                </tbody>
            </table>
            <div class="text-muted">فرمول‌ها در آزمون، کارنامه و مرور هوشمند نیز به‌صورت خودکار رندر می‌شوند.</div>
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var typeSelect = document.getElementById('fld-type');
        var optionsBlock = document.getElementById('options-block');
        var descBlock = document.getElementById('descriptive-block');
        function toggleType() {
            var multi = typeSelect.value === 'multi';
            optionsBlock.classList.toggle('d-none', !multi);
            descBlock.classList.toggle('d-none', multi);
        }
        typeSelect.addEventListener('change', toggleType);
        toggleType();

        // پیش‌نمایش زنده فرمول‌ها
        var body = document.getElementById('question-preview-body');
        var previewText = document.querySelector('textarea[name="text"]');
        var timer = null;
        function renderPreview() {
            body.innerHTML = previewText.value ? previewText.value.replace(/\n/g, '<br>') : 'پیش‌نمایش فرمول‌ها اینجا نمایش داده می‌شود.';
            if (window.MathJax && MathJax.typesetPromise) { MathJax.typesetPromise([body]); }
        }
        previewText.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(renderPreview, 400);
        });
        renderPreview();
    });
</script>
@endpush
