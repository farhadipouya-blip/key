<?php

/*
|--------------------------------------------------------------------------
| مسیرهای عمومی بدون ورود
|--------------------------------------------------------------------------
| استعلام گواهینامه و مشاهده صفحه گواهی
*/

use App\Http\Controllers\CertificateController;

Route::prefix('public')->name('verify.')->group(function () {
    Route::get('/verify-certificate', [CertificateController::class, 'verifyForm'])->name('certificate.form');
    Route::post('/verify-certificate', [CertificateController::class, 'verify'])->name('certificate.check');
    Route::get('/certificate/{certificate:uuid}', [CertificateController::class, 'publicShow'])->name('certificate.show');
});
