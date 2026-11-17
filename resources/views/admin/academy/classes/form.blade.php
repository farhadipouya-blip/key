@extends('layouts.app')

@section('title', $class->exists ? 'ویرایش کلاس' : 'کلاس جدید')
@section('page_title', $class->exists ? 'ویرایش کلاس: ' . $class->title : 'ایجاد کلاس آنلاین')

@section('content')
<x-card title="{{ $class->exists ? 'ویرایش کلاس آنلاین' : 'ایجاد کلاس آنلاین' }}" icon="bi-camera-video">
    <form method="POST" action="{{ $class->exists ? route('admin.classes.update', $class) : route('admin.classes.store') }}">
        @csrf
        @if ($class->exists) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-8">
                <x-input name="title" label="عنوان کلاس" required :value="old('title', $class->title ?? '')"/>
            </div>
            <div class="col-md-4">
                <x-select name="status" label="وضعیت" required :options="$statuses" :value="old('status', $class->status ?? 'scheduled')"/>
            </div>
            <div class="col-12">
                <x-textarea name="description" label="توضیحات" rows="3" :value="old('description', $class->description ?? '')"/>
            </div>
            <div class="col-md-4">
                <x-select name="platform" label="پلتفرم" required :options="$platforms" :value="old('platform', $class->platform ?? 'skyroom')"/>
            </div>
            <div class="col-md-4">
                <x-input name="meeting_id" label="شناسه جلسه" :value="old('meeting_id', $class->meeting_id ?? '')" placeholder="مثلاً key-math-1"/>
            </div>
            <div class="col-md-4">
                <x-input name="duration_minutes" label="مدت (دقیقه)" type="number" :value="old('duration_minutes', $class->duration_minutes ?? '')"/>
            </div>
            <div class="col-md-6">
                <x-input name="join_url" label="لینک ورود دانشجویان" type="url" :value="old('join_url', $class->join_url ?? '')" placeholder="https://..."/>
            </div>
            <div class="col-md-6">
                <x-input name="host_url" label="لینک میزبان/مدرس" type="url" :value="old('host_url', $class->host_url ?? '')" placeholder="https://..."/>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label" for="fld-starts_at">زمان برگزاری</label>
                    <input type="datetime-local" id="fld-starts_at" name="starts_at" class="form-control"
                           value="{{ old('starts_at', $class->starts_at?->format('Y-m-d\TH:i') ?? '') }}">
                    @error('starts_at')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-6">
                <x-select name="course_id" label="دوره مرتبط" :options="$courses" :value="old('course_id', $class->course_id ?? '')"
                          help="کلاس بدون دوره برای همه دانشجویان نمایش داده می‌شود"/>
            </div>
            <div class="col-md-6">
                <x-select name="teacher_id" label="مدرس کلاس" :options="$teachers" :value="old('teacher_id', $class->teacher_id ?? '')"
                          help="اگر خالی بماند، مدرس دوره انتخاب می‌شود"/>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> {{ $class->exists ? 'ذخیره تغییرات' : 'ایجاد کلاس' }}</button>
                <a href="{{ route('admin.classes.index') }}" class="btn btn-outline-secondary">انصراف</a>
            </div>
        </div>
    </form>
</x-card>
@endsection
