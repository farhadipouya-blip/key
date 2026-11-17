@extends('layouts.app')

@section('title', $course->exists ? 'ویرایش دوره' : 'دوره جدید')
@section('page_title', $course->exists ? 'ویرایش دوره: ' . $course->title : 'ایجاد دوره جدید')

@section('content')
@if (! $course->exists)
    <x-card title="اطلاعات دوره جدید" icon="bi-plus-circle">
        <form method="POST" action="{{ route('admin.courses.store') }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            @include('admin.academy.courses._fields')
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> ذخیره دوره</button>
                <a href="{{ route('admin.courses.index') }}" class="btn btn-outline-secondary">انصراف</a>
            </div>
        </form>
    </x-card>
@else
    <div class="row g-3">
        <div class="col-xl-5">
            <x-card title="مشخصات دوره" icon="bi-pencil-square">
                <form method="POST" action="{{ route('admin.courses.update', $course) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('admin.academy.courses._fields')
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> ذخیره تغییرات</button>
                </form>
            </x-card>
        </div>

        <div class="col-xl-7">
            {{-- مدیریت درس‌ها --}}
            <x-card title="مدیریت درس‌ها ({{ fa_num($lessons->count()) }} درس)" icon="bi-collection-play">
                @forelse ($lessons as $i => $lesson)
                    <div class="d-flex align-items-center gap-2 border-bottom py-2 {{ $loop->last ? 'border-bottom-0' : '' }}">
                        <span class="badge bg-secondary">{{ fa_num($lesson->order ?: $i + 1) }}</span>
                        <div class="flex-grow-1">
                            <div class="small fw-bold {{ $editingLesson && $editingLesson->id === $lesson->id ? 'text-primary' : '' }}">{{ $lesson->title }}</div>
                            <div class="small text-muted">
                                @if ($lesson->type === 'video')<i class="bi bi-camera-video"></i> ویدیو
                                @elseif ($lesson->type === 'pdf')<i class="bi bi-file-earmark-pdf"></i> PDF
                                @else<i class="bi bi-file-text"></i> متن @endif
                                @if ($lesson->duration_minutes) — {{ duration_human($lesson->duration_minutes) }} @endif
                                @if ($lesson->video_path) — <span class="text-success"><i class="bi bi-check"></i> فایل دارد</span> @endif
                                @if ($lesson->is_free_preview) — <span class="text-primary">پیش‌نمایش رایگان</span> @endif
                            </div>
                        </div>
                        <a href="{{ route('admin.courses.edit', array_merge([$course], ['edit_lesson' => $lesson->id])) }}#lesson-form"
                           class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('admin.course.lesson.destroy', [$course, $lesson]) }}"
                              onsubmit="return confirm('حذف درس «{{ $lesson->title }}» مطمئن هستید؟')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                @empty
                    <x-empty icon="bi-collection-play" title="هنوز درسی اضافه نشده است" message="با فرم زیر اولین درس دوره را اضافه کنید."/>
                @endforelse
            </x-card>

            <x-card title="{{ $editingLesson ? 'ویرایش درس: ' . $editingLesson->title : 'افزودن درس جدید' }}" icon="bi-plus-square" bodyClass="pt-3" id="lesson-form">
                <form method="POST" enctype="multipart/form-data"
                      action="{{ $editingLesson ? route('admin.course.lesson.update', [$course, $editingLesson]) : route('admin.course.lesson.store', $course) }}">
                    @csrf
                    @if ($editingLesson)
                        @method('PUT')
                    @endif
                    <div class="row g-3">
                        <div class="col-md-8">
                            <x-input name="title" label="عنوان درس" required :value="old('title', $editingLesson?->title ?? '')"/>
                        </div>
                        <div class="col-md-4">
                            <x-select name="type" label="نوع درس" required
                                      :options="['video' => 'ویدیو', 'text' => 'متن', 'pdf' => 'PDF']"
                                      :value="old('type', $editingLesson?->type ?? 'video')"/>
                        </div>
                        <div class="col-md-4">
                            <x-input name="duration_minutes" label="مدت (دقیقه)" type="number" :value="old('duration_minutes', $editingLesson?->duration_minutes ?? '')"/>
                        </div>
                        <div class="col-md-4">
                            <x-input name="order" label="ترتیب نمایش" type="number"
                                     help="خالی بگذارید تا آخر لیست اضافه شود"
                                     :value="old('order', $editingLesson?->order ?? '')"/>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <x-switch name="is_free_preview" label="پیش‌نمایش رایگان"
                                      :checked="old('is_free_preview', $editingLesson?->is_free_preview ?? false)"/>
                        </div>
                        <div class="col-12">
                            <x-textarea name="description" label="توضیح کوتاه درس" rows="2" :value="old('description', $editingLesson?->description ?? '')"/>
                        </div>
                        <div class="col-12">
                            <x-textarea name="content" label="متن درس (برای نوع متن)" rows="5"
                                        help="برای درس‌های متنی، محتوا در همین فیلد ذخیره می‌شود"
                                        :value="old('content', $editingLesson?->content ?? '')"/>
                        </div>

                        @if ($editingLesson && $editingLesson->video_path)
                            <div class="col-12">
                                <div class="small text-muted border rounded p-2">
                                    <i class="bi bi-paperclip"></i> فایل فعلی:
                                    <a href="{{ storage_public_url($editingLesson->video_path) }}" target="_blank" class="text-decoration-none">مشاهده</a>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="remove_file" value="1" id="fld-remove_file">
                                        <label class="form-check-label small" for="fld-remove_file">حذف فایل فعلی</label>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="fld-video_file">فایل ویدیو (برای نوع ویدیو)</label>
                                <input type="file" id="fld-video_file" name="video_file" class="form-control" accept="video/*,audio/*">
                                <div class="form-text">mp4/webm/ogg/mp3 — حداکثر ۱۰۰ مگابایت</div>
                                @error('video_file')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="fld-pdf_file">فایل PDF (برای نوع PDF)</label>
                                <input type="file" id="fld-pdf_file" name="pdf_file" class="form-control" accept="application/pdf">
                                <div class="form-text">حداکثر ۲۰ مگابایت</div>
                                @error('pdf_file')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <x-input name="media_id" label="شناسه رسانه کتابخانه (اختیاری، برای PDF)" type="number"
                                     help="در صورت اتصال به کتابخانه رسانه، فایل کتابخانه نمایش داده می‌شود"
                                     :value="old('media_id', $editingLesson?->media_id ?? '')"/>
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-{{ $editingLesson ? 'check-lg' : 'plus-lg' }}"></i>
                                {{ $editingLesson ? 'ذخیره تغییرات درس' : 'افزودن درس' }}
                            </button>
                            @if ($editingLesson)
                                <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-outline-secondary">انصراف از ویرایش</a>
                            @endif
                        </div>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
@endif
@endsection
