<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * کتابخانه رسانه: پوشه‌ها، بارگذاری امن چندفایلی، دانلود، تغییر نام، انتقال و حذف گروهی
 */
class MediaController extends Controller
{
    public function __construct(private MediaService $mediaService)
    {
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $folderId = $request->query('folder_id');
        $type = (string) $request->query('type');

        $files = Media::query()
            ->with(['uploader:id,name', 'folder:id,name'])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('file_name', 'like', "%{$q}%")))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($folderId === null || $folderId === '', fn ($query) => $query) // همه فایل‌ها
            ->when($folderId !== null && $folderId !== '' && (int) $folderId > 0, fn ($query) => $query->where('folder_id', (int) $folderId))
            ->when($folderId === '0', fn ($query) => $query->whereNull('folder_id'))
            ->orderByDesc('id')
            ->paginate(24)
            ->withQueryString();

        $folders = MediaFolder::query()
            ->withCount('files')
            ->orderBy('name')
            ->get();

        $counts = [
            'all' => Media::count(),
            'no_folder' => Media::whereNull('folder_id')->count(),
        ];

        $typeOptions = [
            'image' => 'تصاویر',
            'video' => 'ویدیوها',
            'audio' => 'صداها',
            'pdf' => 'PDF',
            'document' => 'اسناد آفیس',
            'archive' => 'آرشیو فشرده',
            'other' => 'سایر',
        ];

        return view('media.index', compact('files', 'folders', 'counts', 'typeOptions', 'q', 'folderId', 'type'));
    }

    /** ساخت پوشه جدید */
    public function storeFolder(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:media_folders,name'],
            'parent_id' => ['nullable', 'integer', 'exists:media_folders,id'],
        ], [
            'name.required' => 'نام پوشه الزامی است.',
            'name.unique' => 'پوشه‌ای با این نام از قبل وجود دارد.',
        ]);

        $folder = $this->mediaService->createFolder($data['name'], (int) $request->user()->id, $data['parent_id'] ?? null);

        AuditLog::record($request->user(), 'media.folder.created', $folder, ['name' => $folder->name]);

        return back()->with('success', 'پوشه «' . $folder->name . '» ساخته شد.');
    }

    /** بارگذاری چندفایلی با اسکن امنیتی */
    public function upload(Request $request)
    {
        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'max:20480'],
            'folder_id' => ['nullable', 'integer', 'exists:media_folders,id'],
            'access' => ['nullable', 'in:public,private,staff'],
        ], [
            'files.required' => 'حداقل یک فایل برای بارگذاری انتخاب کنید.',
            'files.*.max' => 'حجم هر فایل باید کمتر از ۲۰ مگابایت باشد.',
            'files.*.file' => 'فایل ارسالی نامعتبر است.',
        ]);

        $folderId = $request->filled('folder_id') ? (int) $request->input('folder_id') : null;
        $access = $request->input('access', 'private');
        $uploaded = 0;
        $errors = [];

        foreach ($request->file('files', []) as $file) {
            try {
                $media = $this->mediaService->upload($file, $folderId, (int) $request->user()->id, $access);
                AuditLog::record($request->user(), 'media.file.uploaded', $media, [
                    'name' => $media->file_name,
                    'size' => $media->size,
                    'mime' => $media->mime_type,
                    'folder' => $media->folder?->name,
                ]);
                $uploaded++;
            } catch (RuntimeException $e) {
                $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        $message = fa_num($uploaded) . ' فایل با موفقیت بارگذاری شد.';
        if ($errors !== []) {
            $message .= ' فایل‌های ردشده: ' . implode(' | ', $errors);
        }

        return back()->with($uploaded > 0 ? 'success' : 'error', $message);
    }

    /** دانلود فایل (با کنترل مجوز در سطح مسیر) */
    public function download(Request $request, Media $media)
    {
        if (! Storage::disk($media->disk)->exists($media->path)) {
            abort(404, 'فایل در دیسک یافت نشد.');
        }

        $media->increment('downloads');

        AuditLog::record($request->user(), 'media.file.downloaded', $media, ['name' => $media->file_name]);

        return \Storage::disk($media->disk)->download($media->path, $media->file_name);
    }

    /** تغییر نام فایل */
    public function rename(Request $request, Media $media)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
        ], [
            'name.required' => 'نام جدید الزامی است.',
        ]);

        $old = $media->name;
        $media->update(['name' => $data['name']]);

        AuditLog::record($request->user(), 'media.file.renamed', $media, ['old' => $old, 'new' => $data['name']]);

        return back()->with('success', 'نام فایل به «' . $data['name'] . '» تغییر یافت.');
    }

    /** انتقال فایل به پوشه دیگر */
    public function move(Request $request, Media $media)
    {
        $data = $request->validate([
            'folder_id' => ['nullable', 'integer', 'exists:media_folders,id'],
        ]);

        $oldFolder = $media->folder?->name;
        $media->update(['folder_id' => $data['folder_id'] ?? null]);
        $newFolder = $media->folder?->name;

        AuditLog::record($request->user(), 'media.file.moved', $media, [
            'from' => $oldFolder,
            'to' => $newFolder,
        ]);

        return back()->with('success', 'فایل به پوشه «' . ($newFolder ?: 'بدون پوشه') . '» منتقل شد.');
    }

    /** حذف گروهی فایل‌ها */
    public function deleteBulk(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:media,id'],
        ], [
            'ids.required' => 'حداقل یک فایل را انتخاب کنید.',
        ]);

        $mediaFiles = Media::whereIn('id', $data['ids'])->get();
        $deleted = 0;
        foreach ($mediaFiles as $media) {
            $this->mediaService->delete($media);
            AuditLog::record($request->user(), 'media.file.deleted', null, [
                'id' => $media->id,
                'name' => $media->file_name,
            ]);
            $deleted++;
        }

        return back()->with('success', fa_num($deleted) . ' فایل حذف شد.');
    }

    /** حذف تکی فایل */
    public function destroy(Request $request, Media $media)
    {
        $name = $media->file_name;
        $this->mediaService->delete($media);

        AuditLog::record($request->user(), 'media.file.deleted', null, ['id' => $media->id, 'name' => $name]);

        return back()->with('success', 'فایل «' . $name . '» حذف شد.');
    }
}
