<?php

use App\Http\Controllers\RankController;

/*
|--------------------------------------------------------------------------
| ماژول تخمین رتبه و انتخاب رشته
|--------------------------------------------------------------------------
*/

Route::middleware('permission:rank.view')->prefix('rank')->name('rank.')->group(function () {
    // فرم تخمین + تاریخچه تخمین‌ها + نتیجه تاریخی
    Route::get('/', [RankController::class, 'index'])->name('index');

    // محاسبه و ذخیره تخمین
    Route::post('estimate', [RankController::class, 'estimate'])->name('estimate');

    // نتایج تاریخی کارنامه (کالیبراسیون تخمین)
    Route::post('historical', [RankController::class, 'historicalStore'])->name('historical.store');
    Route::delete('historical/{result}', [RankController::class, 'historicalDestroy'])->name('historical.destroy');

    // بانک رشته‌ها/دانشگاه‌ها با شانس قبولی
    Route::get('selection', [RankController::class, 'selection'])->name('selection');
    Route::post('selection/save', [RankController::class, 'selectionSave'])->name('selection.save');

    // انتخاب‌های ذخیره‌شده
    Route::get('selections', [RankController::class, 'selections'])->name('selections');
    Route::get('selections/{selection}', [RankController::class, 'selectionShow'])->name('selections.show');
});
