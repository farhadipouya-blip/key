<?php

use App\Http\Controllers\FileImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ماژول ورود گروهی اطلاعات — مجوز: imports.manage
|--------------------------------------------------------------------------
*/

Route::middleware('permission:imports.manage')->prefix('imports')->name('imports.')->group(function () {
    Route::get('/', [FileImportController::class, 'index'])->name('index');
    Route::post('/', [FileImportController::class, 'store'])->name('store');

    Route::get('/export/questions', [FileImportController::class, 'exportQuestions'])->name('export.questions');
    Route::get('/sample/{type}', [FileImportController::class, 'sample'])->where('type', 'questions|historical|historical_ranks')->name('sample');

    Route::get('/{fileImport}/preview', [FileImportController::class, 'preview'])->name('preview');
    Route::post('/{fileImport}/commit', [FileImportController::class, 'commit'])->name('commit');
});
