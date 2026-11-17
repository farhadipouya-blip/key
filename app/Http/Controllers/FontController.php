<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Font;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * مدیریت فونت: بارگذاری فونت (ttf/otf/woff/woff2)، فعال‌سازی سراسری و حذف
 */
class FontController extends Controller
{
    /** پسوندها و امضاهای بایت مجاز فونت */
    private const FONT_TYPES = [
        'ttf' => ['application/font-sfnt', 'font/sfnt', 'font/ttf', 'application/x-font-ttf', 'application/truetype', 'application/octet-stream', 'application/vnd.ms-opentype'],
        'otf' => ['font/otf', 'application/vnd.ms-opentype', 'application/font-sfnt', 'font/sfnt', 'application/x-font-otf', 'application/octet-stream'],
        'woff' => ['font/woff', 'application/font-woff', 'application/x-font-woff', 'application/octet-stream'],
        'woff2' => ['font/woff2', 'application/font-woff2', 'application/x-font-woff2', 'application/octet-stream'],
    ];

    /** امضای بایت اولیه هر قالب فونت */
    private const FONT_MAGIC = [
        'ttf' => ["\x00\x01\x00\x00", "true", "ttcf"],
        'otf' => ["OTTO"],
        'woff' => ["wOFF"],
        'woff2' => ["wOF2"],
    ];

    /** نگاشت پسوند به کلیدواژه CSS format */
    private const CSS_FORMAT = ['ttf' => 'truetype', 'otf' => 'opentype', 'woff' => 'woff', 'woff2' => 'woff2'];

    public function index()
    {
        $fonts = Font::query()->with('uploader:id,name')->orderByDesc('id')->get();
        $activeFamily = setting('fonts.active_family');
        $weightsOptions = [300 => '۳۰۰ نازک', 400 => '۴۰۰ معمولی', 500 => '۵۰۰ متوسط', 600 => '۶۰۰ نیم‌ضخیم', 700 => '۷۰۰ ضخیم', 800 => '۸۰۰ بسیار ضخیم'];

        return view('fonts.index', compact('fonts', 'activeFamily', 'weightsOptions'));
    }

    /** بارگذاری فونت جدید — فقط ttf/otf/woff/woff2 تا ۵ مگابایت */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'file' => ['required', 'file', 'max:5120'],
            'weights' => ['nullable', 'array'],
            'weights.*' => ['integer', 'in:100,200,300,400,500,600,700,800,900'],
            'license_name' => ['nullable', 'string', 'max:120'],
            'license_note' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'نام فونت الزامی است.',
            'file.required' => 'انتخاب فایل فونت الزامی است.',
            'file.max' => 'حجم فایل فونت باید کمتر از ۵ مگابایت باشد.',
            'file.uploaded' => 'حجم فایل فونت باید کمتر از ۵ مگابایت باشد.',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        // فقط پسوندهای مجاز
        if (! isset(self::FONT_TYPES[$extension])) {
            return back()->with('error', 'قالب فونت «' . $extension . '» پشتیبانی نمی‌شود. فقط ttf، otf، woff و woff2 مجاز است.')->withInput();
        }

        // بررسی MIME واقعی با finfo
        try {
            $mime = app(MediaService::class)->detectMime($file);
        } catch (\Throwable) {
            $mime = '';
        }
        if (! in_array($mime, self::FONT_TYPES[$extension], true)) {
            return back()->with('error', 'امضای فایل با قالب فونت همخوان نیست (MIME: ' . $mime . ').')->withInput();
        }

        // بررسی امضای بایت اولیه فایل (حتی اگر octet-stream گزارش شود)
        if (! $this->magicMatches($file->getRealPath(), $extension)) {
            return back()->with('error', 'محتوای فایل یک فونت معتبر «' . strtoupper($extension) . '» نیست.')->withInput();
        }

        $weights = array_values(array_unique(array_map('intval', $data['weights'] ?? [400])));
        if ($weights === []) {
            $weights = [400];
        }

        $slug = Str::slug($data['name']);
        if ($slug === '' || Font::where('slug', $slug)->exists()) {
            $slug = 'font-' . Str::lower(Str::random(8));
        }

        $directory = 'fonts';
        $stored = $file->storeAs($directory, $slug . '-' . Str::random(8) . '.' . $extension, 'public');

        $font = Font::create([
            'name' => $data['name'],
            'family' => $data['name'],
            'slug' => $slug,
            'weights' => $weights,
            'file_path' => $stored,
            'format' => $extension,
            'license_name' => $data['license_name'] ?? null,
            'license_note' => $data['license_note'] ?? null,
            'status' => 'installed',
            'uploaded_by' => $request->user()->id,
        ]);

        AuditLog::record($request->user(), 'fonts.font.uploaded', $font, [
            'name' => $font->name,
            'format' => $font->format,
            'weights' => $weights,
            'size' => $file->getSize(),
        ]);

        $this->regenerateActiveCss();

        return back()->with('success', 'فونت «' . $font->name . '» با موفقیت بارگذاری شد. برای اعمال در کل سامانه، آن را فعال کنید.');
    }

    /** فعال‌سازی فونت در کل سامانه */
    public function activate(Request $request, Font $font)
    {
        setting_set('fonts.active_family', $font->family, 'text', 'general', 'فونت فعال سامانه');

        AuditLog::record($request->user(), 'fonts.font.activated', $font, ['family' => $font->family]);

        $this->regenerateActiveCss();

        return back()->with('success', 'فونت «' . $font->name . '» فعال شد — فونت در کل سامانه اعمال شد.');
    }

    /** غیرفعال‌سازی و بازگشت به فونت پیش‌فرض */
    public function deactivate(Request $request, Font $font)
    {
        setting_set('fonts.active_family', '', 'text', 'general', 'فونت فعال سامانه');

        AuditLog::record($request->user(), 'fonts.font.deactivated', $font, ['family' => $font->family]);

        $this->regenerateActiveCss();

        return back()->with('success', 'فونت «' . $font->name . '» غیرفعال شد و سامانه به فونت پیش‌فرض بازگشت.');
    }

    /** حذف فونت — فونت فعال قابل حذف نیست */
    public function destroy(Request $request, Font $font)
    {
        if (setting('fonts.active_family') === $font->family) {
            return back()->with('error', 'فونت «' . $font->name . '» در حال استفاده است؛ ابتدا آن را غیرفعال کنید.');
        }

        Storage::disk('public')->delete($font->file_path);
        $name = $font->name;
        $font->delete();

        AuditLog::record($request->user(), 'fonts.font.deleted', null, ['name' => $name]);

        $this->regenerateActiveCss();

        return back()->with('success', 'فونت «' . $name . '» حذف شد.');
    }

    /** بازنویسی CSS فونت‌های فعال (پوشه public/assets/css) */
    private function regenerateActiveCss(): void
    {
        $css = $this->baseVazirmatnCss();

        $activeFamily = setting('fonts.active_family');
        if ($activeFamily) {
            $font = Font::query()->where('family', $activeFamily)->first();
            if ($font) {
                foreach ((array) ($font->weights ?: [400]) as $weight) {
                    $css .= "@font-face { font-family: '{$font->family}'; src: url('" . asset('storage/' . $font->file_path) . "') format('" . (self::CSS_FORMAT[$font->format] ?? 'woff2') . "'); font-weight: {$weight}; font-display: swap; }\n";
                }
            }
        }

        try {
            file_put_contents(public_path('assets/css/active-font.css'), $css);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** فونت پایه وزیرمتن برای نمایش صحیح کل سامانه */
    private function baseVazirmatnCss(): string
    {
        $faces = [
            400 => 'Vazirmatn-Regular', 500 => 'Vazirmatn-Medium', 600 => 'Vazirmatn-SemiBold',
            700 => 'Vazirmatn-Bold', 800 => 'Vazirmatn-ExtraBold',
        ];
        $css = '';
        foreach ($faces as $weight => $file) {
            $css .= "@font-face { font-family: 'Vazirmatn'; src: url('../fonts/{$file}.woff2') format('woff2'); font-weight: {$weight}; font-display: swap; }\n";
        }

        return $css;
    }

    /** تطبیق امضای بایت فایل با قالب فونت */
    private function magicMatches(string $realPath, string $extension): bool
    {
        $head = (string) @file_get_contents($realPath, false, null, 0, 4);
        foreach (self::FONT_MAGIC[$extension] ?? [] as $magic) {
            if (str_starts_with($head, $magic)) {
                return true;
            }
        }

        return false;
    }
}
