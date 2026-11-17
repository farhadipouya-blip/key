<?php

use App\Http\Controllers\PdfController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ماژول مطالعه PDF: کتابخانه فایل‌های مجاز، کتاب‌خوان با هایلایت و پیشرفت
|--------------------------------------------------------------------------
*/

Route::middleware('permission:academy.learn')->prefix('pdf')->name('pdf.')->group(function () {
    Route::get('/', [PdfController::class, 'index'])->name('index');

    // کتاب‌خوان و جریان فایل (بدون افشای مسیر ذخیره‌سازی)
    Route::get('{media}/read', [PdfController::class, 'read'])->name('read');
    Route::get('{media}/stream', [PdfController::class, 'stream'])->name('stream');

    // ثبت پیشرفت مطالعه و هایلایت‌ها
    Route::post('{media}/progress', [PdfController::class, 'progress'])->name('progress');
    Route::post('{media}/highlight', [PdfController::class, 'highlight'])->name('highlight');
    Route::post('{media}/highlight/{highlight}/delete', [PdfController::class, 'deleteHighlight'])->name('highlight.delete');
});
