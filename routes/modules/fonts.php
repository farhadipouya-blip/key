<?php

use App\Http\Controllers\FontController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ماژول مدیریت فونت — مجوز: fonts.manage
|--------------------------------------------------------------------------
*/

Route::middleware('permission:fonts.manage')->prefix('fonts')->name('fonts.')->group(function () {
    Route::get('/', [FontController::class, 'index'])->name('index');
    Route::post('/', [FontController::class, 'store'])->name('store');

    Route::post('/{font}/activate', [FontController::class, 'activate'])->name('activate');
    Route::post('/{font}/deactivate', [FontController::class, 'deactivate'])->name('deactivate');
    Route::post('/{font}/delete', [FontController::class, 'destroy'])->name('destroy');
});
