<?php

namespace App\Http\Controllers;

use App\Models\CourseEnrollment;
use App\Models\Highlight;
use App\Models\Lesson;
use App\Models\Media;
use App\Models\PdfRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * ماژول مطالعه PDF: فهرست فایل‌های مجاز، کتاب‌خوان با پیشرفت مطالعه و هایلایت/یادداشت
 */
class PdfController extends Controller
{
    /** فهرست PDFهای قابل مطالعه برای کاربر جاری */
    public function index(Request $request)
    {
        $user = $request->user();

        // PDFهای عمومی + فایل‌های شخصی خود کاربر
        $mediaQuery = Media::query()
            ->where('type', 'pdf')
            ->where(function ($q) use ($user) {
                $q->where('access', 'public')
                    ->orWhere('user_id', $user->id);
            });

        $media = $mediaQuery->get()->map(function (Media $item) use ($user) {
            $item->source = $item->user_id === $user->id ? 'شخصی' : 'کتابخانه عمومی';
            $item->source_badge = $item->user_id === $user->id ? 'secondary' : 'info';
            $item->course = null;

            return $item;
        });

        // PDFهای درس‌های دوره‌های ثبت‌نام‌شده کاربر
        $enrolledCourseIds = CourseEnrollment::query()
            ->where('user_id', $user->id)
            ->pluck('course_id');

        if ($enrolledCourseIds->isNotEmpty()) {
            $lessonPdfIds = Lesson::query()
                ->whereIn('course_id', $enrolledCourseIds)
                ->where('type', 'pdf')
                ->whereNotNull('media_id')
                ->with('course:id,title')
                ->get()
                ->mapWithKeys(function (Lesson $lesson) {
                    return [$lesson->media_id => $lesson->course?->title];
                });

            $courseMedia = Media::query()
                ->where('type', 'pdf')
                ->whereIn('id', $lessonPdfIds->keys())
                ->get()
                ->map(function (Media $item) use ($lessonPdfIds) {
                    $item->source = 'دوره: ' . ($lessonPdfIds[$item->id] ?? '—');
                    $item->source_badge = 'success';
                    $item->course = $lessonPdfIds[$item->id] ?? null;

                    return $item;
                });

            $media = $media->merge($courseMedia);
        }

        // یکتاسازی بر اساس شناسه رسانه
        $media = $media->unique('id')->values();

        // پیشرفت مطالعه و تعداد هایلایت‌های کاربر
        $reads = PdfRead::query()->where('user_id', $user->id)->get()->keyBy('media_id');
        $highlightCounts = Highlight::query()
            ->where('user_id', $user->id)
            ->selectRaw('media_id, COUNT(*) as c')
            ->groupBy('media_id')
            ->pluck('c', 'media_id');

        $media = $media->map(function (Media $item) use ($reads, $highlightCounts) {
            $item->read = $reads->get($item->id);
            $item->highlights_count = (int) ($highlightCounts[$item->id] ?? 0);

            return $item;
        });

        // خوانده‌شده‌ها و آخرین مطالعه اول
        $media = $media->sort(function ($a, $b) {
            $aRead = $a->read?->last_read_at?->timestamp ?? 0;
            $bRead = $b->read?->last_read_at?->timestamp ?? 0;
            if ($aRead !== $bRead) {
                return $bRead <=> $aRead;
            }

            return strcmp($a->name, $b->name);
        })->values();

        return view('pdf.index', compact('media'));
    }

    /** صفحه کتاب‌خوان */
    public function read(Request $request, Media $media)
    {
        $user = $request->user();

        if (! $this->canRead($user, $media)) {
            abort(403, 'به این فایل دسترسی ندارید.');
        }

        $read = PdfRead::query()
            ->where('user_id', $user->id)
            ->where('media_id', $media->id)
            ->first();

        $highlights = Highlight::query()
            ->where('user_id', $user->id)
            ->where('media_id', $media->id)
            ->orderByDesc('page')
            ->orderByDesc('id')
            ->get();

        // تخمین تعداد صفحات سمت سرور (pdf.js هم پس از بارگذاری عدد دقیق را ثبت می‌کند)
        $totalPages = $read?->total_pages ?: $this->estimatePageCount($media);

        return view('pdf.reader', compact('media', 'read', 'highlights', 'totalPages'));
    }

    /** جریان فایل به‌صورت inline (بدون افشای مسیر دیسک) */
    public function stream(Request $request, Media $media)
    {
        if (! $this->canRead($request->user(), $media)) {
            abort(403, 'به این فایل دسترسی ندارید.');
        }

        $disk = Storage::disk($media->disk ?: 'local');

        if (! $disk->exists($media->path)) {
            abort(404, 'فایل یافت نشد.');
        }

        return $disk->response($media->path, $media->file_name, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ], 'inline');
    }

    /** ثبت پیشرفت مطالعه (صفحه جاری + تعداد کل) */
    public function progress(Request $request, Media $media)
    {
        if (! $this->canRead($request->user(), $media)) {
            abort(403);
        }

        $data = $request->validate([
            'page' => ['required', 'integer', 'min:1', 'max:100000'],
            'total_pages' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);

        $totalPages = $data['total_pages'] ?? null;
        $progress = $totalPages ? (int) min(100, round($data['page'] / $totalPages * 100)) : 0;

        $read = PdfRead::query()->updateOrCreate(
            ['media_id' => $media->id, 'user_id' => $request->user()->id],
            [
                'current_page' => $data['page'],
                'total_pages' => $totalPages,
                'progress' => $progress,
                'last_read_at' => now(),
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'progress' => $read->progress,
                'current_page' => $read->current_page,
                'total_pages' => $read->total_pages,
            ]);
        }

        return back();
    }

    /** ذخیره هایلایت/یادداشت */
    public function highlight(Request $request, Media $media)
    {
        if (! $this->canRead($request->user(), $media)) {
            abort(403);
        }

        $data = $request->validate([
            'page' => ['required', 'integer', 'min:1', 'max:100000'],
            'text' => ['nullable', 'string', 'max:2000'],
            'note' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'in:yellow,green,red,blue,purple'],
        ], [
            'page.required' => 'شماره صفحه الزامی است.',
            'text.max' => 'متن هایلایت حداکثر ۲۰۰۰ کاراکتر است.',
            'note.max' => 'یادداشت حداکثر ۱۰۰۰ کاراکتر است.',
        ]);

        if (trim((string) ($data['text'] ?? '')) === '' && trim((string) ($data['note'] ?? '')) === '') {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'متن یا یادداشت را وارد کنید.'], 422);
            }

            return back()->with('error', 'برای ثبت هایلایت، متن یا یادداشت لازم است.');
        }

        $highlight = Highlight::create([
            'media_id' => $media->id,
            'user_id' => $request->user()->id,
            'page' => $data['page'],
            'text' => $data['text'] ?? null,
            'note' => $data['note'] ?? null,
            'color' => $data['color'] ?? 'yellow',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'highlight' => [
                    'id' => $highlight->id,
                    'page' => $highlight->page,
                    'text' => $highlight->text,
                    'note' => $highlight->note,
                    'color' => $highlight->color,
                ],
            ]);
        }

        return back()->with('success', 'هایلایت ذخیره شد.');
    }

    /** حذف هایلایت (فقط مالک) */
    public function deleteHighlight(Request $request, Media $media, Highlight $highlight)
    {
        if ($highlight->user_id !== $request->user()->id || $highlight->media_id !== $media->id) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'دسترسی مجاز نیست.'], 403);
            }
            abort(403, 'این هایلایت متعلق به شما نیست.');
        }

        $highlight->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'هایلایت حذف شد.');
    }

    // ------------------------------------------------------------------
    // متدهای کمکی
    // ------------------------------------------------------------------

    /** بررسی مجوز مطالعه یک رسانه */
    private function canRead($user, Media $media): bool
    {
        if ($media->type !== 'pdf') {
            return false;
        }

        // عمومی
        if ($media->access === 'public') {
            return true;
        }

        // فایل شخصی خود کاربر
        if ((int) $media->user_id === (int) $user->id) {
            return true;
        }

        // کارمند دارای مجوز مدیریت رسانه
        if ($user->isStaff() && $user->canAccess('media.manage')) {
            return true;
        }

        // پیوست به درس دوره‌ای که کاربر در آن ثبت‌نام دارد
        $courseIds = Lesson::query()
            ->where('type', 'pdf')
            ->where('media_id', $media->id)
            ->pluck('course_id');

        if ($courseIds->isNotEmpty()) {
            $enrolled = CourseEnrollment::query()
                ->where('user_id', $user->id)
                ->whereIn('course_id', $courseIds)
                ->exists();

            if ($enrolled) {
                return true;
            }
        }

        return false;
    }

    /** تخمین تعداد صفحات PDF از ساختار فایل */
    private function estimatePageCount(Media $media): ?int
    {
        try {
            $disk = Storage::disk($media->disk ?: 'local');
            if (! $disk->exists($media->path)) {
                return null;
            }

            $contents = $disk->get($media->path);
            if (! is_string($contents)) {
                return null;
            }

            // ابتدا شمارنده شئ Pages در سند
            if (preg_match('~/Type\s*/Pages[^>]*?/Count\s+(\d+)~s', $contents, $m)
                || preg_match('~/Count\s+(\d+)[^>]*?/Type\s*/Pages~s', $contents, $m)) {
                return (int) $m[1];
            }

            // در نبود آن، تعداد اشیاء صفحه
            $count = preg_match_all('~/Type\s*/Page[^s]~', $contents);

            return $count > 0 ? $count : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
