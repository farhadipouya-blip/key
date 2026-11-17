<?php

use App\Http\Controllers\SummaryController;

/*
|--------------------------------------------------------------------------
| ماژول بانک خلاصه و نکته
|--------------------------------------------------------------------------
*/

Route::middleware('permission:summaries.view')->prefix('summaries')->name('summaries.')->group(function () {
    Route::get('/', [SummaryController::class, 'index'])->name('index');
    Route::get('/create', [SummaryController::class, 'create'])->name('create');
    Route::post('/', [SummaryController::class, 'store'])->name('store');
    Route::get('/{summary}', [SummaryController::class, 'show'])->name('show');
    Route::get('/{summary}/edit', [SummaryController::class, 'edit'])->name('edit');
    Route::put('/{summary}', [SummaryController::class, 'update'])->name('update');
    Route::delete('/{summary}', [SummaryController::class, 'destroy'])->name('destroy');
    Route::post('/{summary}/notes', [SummaryController::class, 'storeNote'])->name('note.store');
    Route::post('/{summary}/bookmark', [SummaryController::class, 'bookmark'])->name('bookmark');
});

// پنل مدیریت خلاصه‌های عمومی
Route::middleware('permission:summaries.manage')->prefix('admin/summaries')->name('admin.summaries.')->group(function () {
    Route::get('/', [SummaryController::class, 'adminIndex'])->name('index');
    Route::post('/', [SummaryController::class, 'adminStore'])->name('store');
    Route::delete('/{summary}', [SummaryController::class, 'adminDestroy'])->name('destroy');
});
