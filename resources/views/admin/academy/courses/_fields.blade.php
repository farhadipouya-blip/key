<div class="row g-3">
    <div class="col-md-8">
        <x-input name="title" label="عنوان دوره" required :value="old('title', $course->title ?? '')"/>
    </div>
    <div class="col-md-4">
        <x-input name="duration_hours" label="مدت دوره (ساعت)" type="number" :value="old('duration_hours', $course->duration_hours ?? '')"/>
    </div>
    <div class="col-12">
        <x-textarea name="description" label="توضیحات دوره" rows="4" :value="old('description', $course->description ?? '')"/>
    </div>
    <div class="col-md-6">
        <x-select name="category_id" label="دسته‌بندی" :options="$categories" :value="old('category_id', $course->category_id ?? '')"/>
    </div>
    <div class="col-md-6">
        <x-select name="teacher_id" label="مدرس" :options="$teachers" :value="old('teacher_id', $course->teacher_id ?? '')"/>
    </div>
    <div class="col-md-6">
        <x-switch name="is_free" label="دوره رایگان است" :checked="old('is_free', $course->is_free ?? true)"/>
    </div>
    <div class="col-md-6">
        <x-input name="price" label="قیمت دوره (تومان)" type="number" money
                 help="در صورت رایگان بودن دوره، قیمت نادیده گرفته می‌شود"
                 :value="old('price', $course->price ?? 0)"/>
    </div>
    <div class="col-md-6">
        <x-switch name="is_published" label="انتشار دوره" :checked="old('is_published', $course->is_published ?? false)"
                  help="دوره منتشرشده در آکادمی دانشجویان نمایش داده می‌شود"/>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="fld-cover">تصویر جلد</label>
            <input type="file" id="fld-cover" name="cover" class="form-control" accept="image/*">
            <div class="form-text">jpg/png/webp — حداکثر ۴ مگابایت</div>
            @error('cover')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
            @if ($course->cover_path)
                <img src="{{ storage_public_url($course->cover_path) }}" alt="جلد فعلی" class="img-thumbnail mt-2" style="max-height:110px">
            @endif
        </div>
    </div>
</div>
