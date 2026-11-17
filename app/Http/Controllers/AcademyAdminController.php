<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * مدیریت آکادمی: دوره‌ها و درس‌ها (فقط کاربران با مجوز academy.manage)
 */
class AcademyAdminController extends Controller
{
    /** گزینه‌های مدرس‌ها (کاربران با نقش مدرس یا مدیر) */
    private function teacherOptions(): array
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['teacher', 'admin', 'superadmin', 'content_creator']))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->pluck('name', 'id')
            ->all();
    }

    private function courseCategories(): array
    {
        return Category::query()->where('type', 'course')->orderBy('order')->pluck('title', 'id')->all();
    }

    /** ساخت یکتای slug از عنوان فارسی یا لاتین */
    private function uniqueSlug(string $title, string $table, ?int $ignoreId = null): string
    {
        $base = Str::slug($title, '-', null) ?: Str::slug(mb_substr($title, 0, 20), '-', null);
        if (! $base) {
            $base = 'item-' . Str::lower(Str::random(8));
        }
        $slug = $base;
        $i = 1;
        while (
            ($table === 'courses' ? Course::where('slug', $slug) : \App\Models\Article::where('slug', $slug))
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    // ------------------------------------------------------------------
    // دوره‌ها
    // ------------------------------------------------------------------

    public function coursesIndex(Request $request)
    {
        $courses = Course::query()
            ->with(['teacher:id,name', 'category:id,title'])
            ->withCount(['lessons', 'enrollments'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%' . $request->q . '%')
                ->orWhere('description', 'like', '%' . $request->q . '%')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('status'), fn ($q) => $q->where('is_published', $request->status === 'published'))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'all' => Course::count(),
            'published' => Course::where('is_published', true)->count(),
            'paid' => Course::where('is_free', false)->count(),
            'lessons' => Lesson::count(),
        ];

        $categories = $this->courseCategories();

        return view('admin.academy.courses.index', compact('courses', 'stats', 'categories'));
    }

    public function coursesCreate()
    {
        $categories = $this->courseCategories();
        $teachers = $this->teacherOptions();
        $course = new Course(['is_free' => true, 'is_published' => false, 'price' => 0]);

        return view('admin.academy.courses.form', compact('course', 'categories', 'teachers'));
    }

    public function coursesStore(Request $request)
    {
        $data = $this->validateCourse($request);

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $request->file('cover')->store('uploads/covers', 'public');
        }

        $data['slug'] = $this->uniqueSlug($data['title'], 'courses');
        $data['created_by'] = $request->user()->id;
        $data['is_free'] = $request->boolean('is_free');
        $data['is_published'] = $request->boolean('is_published');
        if ($data['is_free']) {
            $data['price'] = 0;
        }

        $course = Course::create($data);

        AuditLog::record($request->user(), 'course.created', $course, ['title' => $course->title]);

        return redirect()->route('admin.courses.edit', $course)
            ->with('success', 'دوره «' . $course->title . '» ایجاد شد. اکنون می‌توانید درس‌های آن را اضافه کنید.');
    }

    public function coursesEdit(Request $request, Course $course)
    {
        $course->loadCount(['lessons', 'enrollments']);
        $lessons = $course->lessons()->orderBy('order')->orderBy('id')->get();

        // درس در حالت ویرایش (پارامتر edit_lesson)
        $editingLesson = null;
        if ($request->filled('edit_lesson')) {
            $editingLesson = $course->lessons()->where('id', (int) $request->edit_lesson)->first();
        }

        $categories = $this->courseCategories();
        $teachers = $this->teacherOptions();

        return view('admin.academy.courses.form', compact('course', 'categories', 'teachers', 'lessons', 'editingLesson'));
    }

    public function coursesUpdate(Request $request, Course $course)
    {
        $data = $this->validateCourse($request);

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $request->file('cover')->store('uploads/covers', 'public');
        }

        $data['is_free'] = $request->boolean('is_free');
        $data['is_published'] = $request->boolean('is_published');
        if ($data['is_free']) {
            $data['price'] = 0;
        }

        $course->update($data);

        AuditLog::record($request->user(), 'course.updated', $course, ['title' => $course->title]);

        return redirect()->route('admin.courses.edit', $course)
            ->with('success', 'تغییرات دوره با موفقیت ذخیره شد.');
    }

    public function coursesDestroy(Request $request, Course $course)
    {
        $title = $course->title;
        $course->delete();

        AuditLog::record($request->user(), 'course.deleted', null, ['title' => $title, 'id' => $course->id]);

        return redirect()->route('admin.courses.index')
            ->with('success', 'دوره «' . $title . '» و همه درس‌های آن حذف شد.');
    }

    /** قواعد اعتبارسنجی مشترک دوره */
    private function validateCourse(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'duration_hours' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_free' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'title.required' => 'عنوان دوره الزامی است.',
            'title.max' => 'عنوان دوره حداکثر ۲۰۰ نویسه است.',
            'category_id.exists' => 'دسته انتخابی معتبر نیست.',
            'teacher_id.exists' => 'مدرس انتخابی معتبر نیست.',
            'price.numeric' => 'قیمت باید عدد باشد.',
            'price.min' => 'قیمت نمی‌تواند منفی باشد.',
            'duration_hours.integer' => 'مدت دوره باید عدد صحیح باشد.',
            'cover.image' => 'فایل جلد باید تصویر باشد.',
            'cover.mimes' => 'فرمت تصویر جلد باید jpg، png یا webp باشد.',
            'cover.max' => 'حجم تصویر جلد حداکثر ۴ مگابایت است.',
        ]);
    }

    // ------------------------------------------------------------------
    // درس‌ها
    // ------------------------------------------------------------------

    public function lessonStore(Request $request, Course $course)
    {
        $data = $this->validateLesson($request);

        if ($request->hasFile('video_file')) {
            $data['video_path'] = $request->file('video_file')->store('uploads/lessons/videos', 'public');
        } elseif ($request->hasFile('pdf_file')) {
            $data['video_path'] = $request->file('pdf_file')->store('uploads/lessons/pdfs', 'public');
        }

        $maxOrder = (int) $course->lessons()->max('order');
        $data['course_id'] = $course->id;
        $data['order'] = $data['order'] ?: ($maxOrder + 1);
        $data['is_free_preview'] = $request->boolean('is_free_preview');

        $lesson = Lesson::create($data);

        AuditLog::record($request->user(), 'lesson.created', $lesson, ['course' => $course->id, 'type' => $lesson->type]);

        return redirect()->route('admin.courses.edit', $course)
            ->with('success', 'درس «' . $lesson->title . '» اضافه شد.');
    }

    public function lessonUpdate(Request $request, Course $course, Lesson $lesson)
    {
        abort_unless($lesson->course_id === $course->id, 404);

        $data = $this->validateLesson($request, $lesson);

        if ($request->hasFile('video_file')) {
            $data['video_path'] = $request->file('video_file')->store('uploads/lessons/videos', 'public');
        } elseif ($request->hasFile('pdf_file')) {
            $data['video_path'] = $request->file('pdf_file')->store('uploads/lessons/pdfs', 'public');
        } elseif ($request->boolean('remove_file')) {
            // حذف فایل قبلی در صورت انتخاب گزینه حذف فایل
            $data['video_path'] = null;
        } else {
            unset($data['video_path']);
        }

        $data['is_free_preview'] = $request->boolean('is_free_preview');

        $lesson->update($data);

        AuditLog::record($request->user(), 'lesson.updated', $lesson, ['course' => $course->id]);

        return redirect()->route('admin.courses.edit', $course)
            ->with('success', 'درس «' . $lesson->title . '» به‌روزرسانی شد.');
    }

    public function lessonDestroy(Request $request, Course $course, Lesson $lesson)
    {
        abort_unless($lesson->course_id === $course->id, 404);

        $title = $lesson->title;
        $lesson->delete();

        AuditLog::record($request->user(), 'lesson.deleted', null, ['title' => $title, 'course' => $course->id]);

        return redirect()->route('admin.courses.edit', $course)
            ->with('success', 'درس «' . $title . '» حذف شد.');
    }

    private function validateLesson(Request $request, ?Lesson $lesson = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', Rule::in(['video', 'text', 'pdf'])],
            'content' => ['nullable', 'string', 'max:60000'],
            'media_id' => ['nullable', 'integer', 'exists:media,id'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'order' => ['nullable', 'integer', 'min:1', 'max:999'],
            'is_free_preview' => ['nullable', 'boolean'],
            'video_file' => ['nullable', 'file', 'max:102400', 'mimes:mp4,webm,ogg,mp3'],
            'pdf_file' => ['nullable', 'file', 'max:20480', 'mimes:pdf'],
            'remove_file' => ['nullable', 'boolean'],
        ], [
            'title.required' => 'عنوان درس الزامی است.',
            'type.required' => 'نوع درس الزامی است.',
            'type.in' => 'نوع درس باید ویدیو، متن یا PDF باشد.',
            'media_id.exists' => 'فایل رسانه انتخابی در کتابخانه یافت نشد.',
            'duration_minutes.integer' => 'مدت درس باید عدد صحیح باشد.',
            'order.integer' => 'ترتیب باید عدد صحیح باشد.',
            'video_file.mimes' => 'فرمت فایل ویدیو باید mp4، webm، ogg یا mp3 باشد.',
            'video_file.max' => 'حجم فایل ویدیو حداکثر ۱۰۰ مگابایت است.',
            'pdf_file.mimes' => 'فایل باید PDF باشد.',
            'pdf_file.max' => 'حجم فایل PDF حداکثر ۲۰ مگابایت است.',
            'video_file.required_if' => 'برای درس ویدیویی، بارگذاری فایل ویدیو الزامی است.',
        ]);

        // کنترل منطقی: درس ویدیویی جدید باید فایل ویدیو داشته باشد
        if (
            $data['type'] === 'video'
            && ! $request->hasFile('video_file')
            && ($lesson?->video_path === null || $request->boolean('remove_file'))
        ) {
            $request->validate([
                'video_file' => ['required'],
            ], ['video_file.required' => 'برای درس ویدیویی، بارگذاری فایل ویدیو الزامی است.']);
        }

        return $data;
    }
}
