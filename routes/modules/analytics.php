<?php

use App\Http\Controllers\AnalyticsController;

/*
|--------------------------------------------------------------------------
| ماژول تحلیل عملکرد
|--------------------------------------------------------------------------
| داشبورد تحلیل دانشجوی جاری: analytics.view
| مشاهده تحلیل دانشجوی خاص توسط مشاور/مدیر: counselor.panel یا users.manage
*/

Route::middleware('permission:analytics.view')->prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/', [AnalyticsController::class, 'index'])->name('index');

    // تحلیل دانشجوی خاص (مشاهده توسط مشاور/مدیر)
    Route::get('{user}', [AnalyticsController::class, 'user'])->name('user');
});
