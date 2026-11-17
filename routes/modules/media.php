<?php

use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ماژول کتابخانه رسانه — مجوز: media.manage
|--------------------------------------------------------------------------
*/

Route::middleware('permission:media.manage')->prefix('media')->name('media.')->group(function () {
    Route::get('/', [MediaController::class, 'index'])->name('index');

    Route::post('/folders', [MediaController::class, 'storeFolder'])->name('folders.store');

    Route::post('/upload', [MediaController::class, 'upload'])->name('upload');
    Route::post('/delete-bulk', [MediaController::class, 'deleteBulk'])->name('delete-bulk');

    Route::get('/{media}/download', [MediaController::class, 'download'])->name('download');
    Route::post('/{media}/rename', [MediaController::class, 'rename'])->name('rename');
    Route::post('/{media}/move', [MediaController::class, 'move'])->name('move');
    Route::delete('/{media}', [MediaController::class, 'destroy'])->name('destroy');
});
