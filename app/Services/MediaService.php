<?php

namespace App\Services;

use App\Models\Media;
use App\Models\MediaFolder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * سرویس کتابخانه رسانه: بارگذاری امن، اسکن فایل، مدیریت پوشه‌ها و حذف
 */
class MediaService
{
    /** حداکثر حجم هر فایل (بایت) */
    public const MAX_SIZE = 20 * 1024 * 1024; // ۲۰ مگابایت

    /**
     * پسوندهای مجاز و امضای MIME مورد قبول برای هر پسوند
     * هر چیزی خارج از این فهرست (مثل php، exe، sh، js، html) به‌صورت کامل مسدود است.
     */
    public const ALLOWED_MIMES = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'gif' => ['image/gif'],
        'pdf' => ['application/pdf'],
        'mp4' => ['video/mp4'],
        'mp3' => ['audio/mpeg', 'audio/mp3'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'csv' => ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'],
        'txt' => ['text/plain'],
    ];

    /** MIMEهای مطلقاً خطرناک — مستقل از پسوند رد می‌شوند */
    private const BLOCKED_MIMES = [
        'text/x-php', 'application/x-httpd-php', 'application/x-httpd-php-source',
        'text/html', 'application/xhtml+xml', 'text/javascript', 'application/javascript',
        'application/x-javascript', 'application/x-sh', 'application/x-shellscript',
        'application/x-executable', 'application/x-dosexec', 'application/x-msdownload',
        'application/x-sharedlib', 'application/octet-stream',
    ];

    /**
     * اسکن امنیتی و ذخیره فایل روی دیسک public + ساخت رکورد Media
     *
     * @throws RuntimeException در صورت نامعتبر بودن فایل
     */
    public function upload(UploadedFile $file, ?int $folderId, int $userId, string $access = 'private'): Media
    {
        $this->scan($file);

        $folder = $folderId ? MediaFolder::findOrFail($folderId) : null;
        $extension = strtolower($file->getClientOriginalExtension());
        $directory = 'media/' . $this->folderSegment($folder);

        $stored = $file->storeAs($directory, now()->getTimestamp() . '-' . Str::random(24) . '.' . $extension, 'public');

        $media = Media::create([
            'user_id' => $userId,
            'folder_id' => $folder?->id,
            'name' => mb_substr(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 0, 190),
            'file_name' => mb_substr($file->getClientOriginalName(), 0, 290),
            'path' => $stored,
            'disk' => 'public',
            'mime_type' => $this->detectMime($file),
            'size' => $file->getSize(),
            'type' => Media::typeFromMime($this->detectMime($file)),
            'access' => in_array($access, ['public', 'private', 'staff'], true) ? $access : 'private',
            'meta' => [
                'extension' => $extension,
                'folder_name' => $folder?->name,
            ],
            'is_scanned' => true,
            'scan_result' => 'ok',
        ]);

        return $media;
    }

    /**
     * اسکن امنیتی فایل: پسوند، MIME واقعی (finfo) و محتوا — فایل مشکوک حذف و رد می‌شود
     *
     * @throws RuntimeException
     */
    public function scan(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new RuntimeException('فایل به‌درستی بارگذاری نشده است.');
        }

        if ($file->getSize() > self::MAX_SIZE) {
            throw new RuntimeException('حجم فایل بیش از حد مجاز است (حداکثر ۲۰ مگابایت).');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension === '' || ! isset(self::ALLOWED_MIMES[$extension])) {
            throw new RuntimeException('نوع فایل «' . $extension . '» مجاز نیست. پسوندهای مجاز: jpg، png، webp، gif، pdf، mp4، mp3، zip، doc، docx، xls، xlsx، csv، txt');
        }

        $mime = $this->detectMime($file);
        if (in_array($mime, self::BLOCKED_MIMES, true)) {
            throw new RuntimeException('محتوای فایل خطرناک تشخیص داده شد و بارگذاری مسدود شد.');
        }

        if (! in_array($mime, self::ALLOWED_MIMES[$extension], true)) {
            throw new RuntimeException('امضای واقعی فایل («' . $mime . '») با پسوند «' . $extension . '» همخوانی ندارد.');
        }

        // اسکن محتوای فایل‌های متنی: وجود کد PHP مطلقاً مسدود است
        if (in_array($extension, ['txt', 'csv'], true) || str_starts_with($mime, 'text/')) {
            $head = (string) @file_get_contents($file->getRealPath(), false, null, 0, 8192);
            if (preg_match('/<\?php|<\?=|<script/i', $head)) {
                throw new RuntimeException('محتوای اسکریپت داخل فایل متنی شناسایی شد و بارگذاری مسدود شد.');
            }
        }
    }

    /** تشخیص MIME واقعی فایل با finfo (نه به اعتماد نام فایل یا هدر مرورگر) */
    public function detectMime(UploadedFile $file): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return (string) $finfo->file($file->getRealPath());
    }

    /** قطعه‌ی مسیر پوشه برای ذخیره‌سازی (امن‌سازی نام پوشه) */
    public function folderSegment(?MediaFolder $folder): string
    {
        if (! $folder) {
            return 'general';
        }

        $segment = Str::slug($folder->name);

        return $segment !== '' ? $segment : 'folder-' . $folder->id;
    }

    /** حذف کامل فایل از دیسک و پایگاه داده */
    public function delete(Media $media): void
    {
        Storage::disk('public')->delete($media->path);
        $media->delete();
    }

    /** ساخت پوشه با نام یکتا */
    public function createFolder(string $name, int $userId, ?int $parentId = null): MediaFolder
    {
        return MediaFolder::create([
            'user_id' => $userId,
            'parent_id' => $parentId,
            'name' => mb_substr(trim($name), 0, 120),
        ]);
    }
}
