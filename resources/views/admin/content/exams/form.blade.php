@extends('layouts.app')

@section('title', $exam->exists ? 'مشخصات آزمون' : 'آزمون جدید')
@section('page_title', $exam->exists ? 'مشخصات آزمون: ' . $exam->title : 'ساخت آزمون جدید')

@section('content')
<x-alerts/>

<x-card title="مشخصات آزمون" icon="bi-ui-checks">
    <form method="POST"
          action="{{ $exam->exists ? route('admin.exams.update', $exam) : route('admin.exams.store') }}">
        @csrf
        @if ($exam->exists) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-md-8">
                <x-input name="title" label="عنوان آزمون" :value="old('title', $exam->title)" required placeholder="مثال: آزمون جامع فصل حد — آبان"/>
            </div>
            <div class="col-md-4">
                <label class="form-label">نوع آزمون <span class="text-danger">*</span></label>
                <select name="type" class="form-select">
                    <option value="practice" @if (old('type', $exam->type) === 'practice') selected @endif>تمرین (بدون زمان)</option>
                    <option value="timed" @if (old('type', $exam->type) === 'timed') selected @endif>زمان‌دار</option>
                    <option value="simulator" @if (old('type', $exam->type) === 'simulator') selected @endif>شبیه‌ساز کنکور</option>
                    <option value="homework" @if (old('type', $exam->type) === 'homework') selected @endif>تکلیف درسی</option>
                </select>
                @error('type')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="mt-3">
            <x-textarea name="description" label="توضیحات (اختیاری)" :value="old('description', $exam->description)" rows="2"/>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <x-select name="subject_id" label="درس اصلی (اختیاری)" :options="$subjects" :value="old('subject_id', $exam->subject_id)"/>
            </div>
            <div class="col-md-4">
                <x-input name="duration_minutes" label="مدت (دقیقه)" type="number" min="1" max="600"
                         :value="old('duration_minutes', $exam->duration_minutes)" help="خالی = بدون محدودیت زمان"/>
            </div>
            <div class="col-md-4">
                <x-input name="pass_percent" label="درصد قبولی" type="number" step="1" min="0" max="100" :value="old('pass_percent', $exam->pass_percent)"/>
            </div>
        </div>

        <div class="row g-3 mt-0">
            <div class="col-md-6">
                <label class="form-label">زمان شروع (اختیاری)</label>
                <input type="datetime-local" name="starts_at" class="form-control" dir="ltr"
                       value="{{ old('starts_at', $exam->starts_at?->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">زمان پایان (اختیاری)</label>
                <input type="datetime-local" name="ends_at" class="form-control" dir="ltr"
                       value="{{ old('ends_at', $exam->ends_at?->format('Y-m-d\TH:i')) }}">
            </div>
        </div>

        <div class="row g-3 mt-0">
            <div class="col-md-6">
                <x-input name="negative_value" label="ضریب نمره منفی" type="number" step="0.05" min="0" max="1"
                         :value="old('negative_value', $exam->negative_value)" help="مثال: ۰٫۲۵ — هر پاسخ غلط یک‌چهارم بارم کم می‌کند"/>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="negative_marking" id="fld-negative"
                           @if (old('negative_marking', $exam->negative_marking)) checked @endif>
                    <label class="form-check-label" for="fld-negative">فعال‌سازی نمره منفی</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_public" id="fld-public"
                           @if (old('is_public', $exam->is_public)) checked @endif>
                    <label class="form-check-label" for="fld-public">عمومی (در مرکز آزمون همه دانشجویان)</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="shuffle_questions" id="fld-shuffle-q"
                           @if (old('shuffle_questions', $exam->shuffle_questions)) checked @endif>
                    <label class="form-check-label" for="fld-shuffle-q">ترتیب تصادفی سؤالات</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="shuffle_options" id="fld-shuffle-o"
                           @if (old('shuffle_options', $exam->shuffle_options)) checked @endif>
                    <label class="form-check-label" for="fld-shuffle-o">ترتیب تصادفی گزینه‌ها</label>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> ذخیره</button>
            <a href="{{ route('admin.exams.index') }}" class="btn btn-outline-secondary">بازگشت</a>
        </div>
    </form>
</x-card>
@endsection
