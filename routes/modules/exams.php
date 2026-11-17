<?php

use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StudentExamController;

/*
|--------------------------------------------------------------------------
| ماژول بانک تست و آزمون + مرور هوشمند
|--------------------------------------------------------------------------
*/

Route::middleware('permission:exams.take')->prefix('exams')->name('exams.')->group(function () {
    Route::get('/', [StudentExamController::class, 'hub'])->name('hub');
    Route::get('/history', [StudentExamController::class, 'history'])->name('history');
    Route::get('/practice', [StudentExamController::class, 'practice'])->name('practice');
    Route::get('/{exam}', [StudentExamController::class, 'show'])->name('show');
    Route::post('/{exam}/start', [StudentExamController::class, 'start'])->name('start');
    Route::get('/attempt/{attempt}/take', [StudentExamController::class, 'take'])->name('take');
    Route::post('/attempt/{attempt}/answers/{question}', [StudentExamController::class, 'answer'])->name('answer');
    Route::post('/attempt/{attempt}/finish', [StudentExamController::class, 'finish'])->name('finish');
    Route::get('/attempt/{attempt}/result', [StudentExamController::class, 'result'])->name('result');
});

Route::middleware('permission:reviews.view')->prefix('review')->name('review.')->group(function () {
    Route::get('/', [ReviewController::class, 'index'])->name('index');
    Route::get('/session', [ReviewController::class, 'session'])->name('session');
    Route::post('/submit', [ReviewController::class, 'submit'])->name('submit');
    Route::post('/add', [ReviewController::class, 'add'])->name('add');
    Route::get('/history', [ReviewController::class, 'history'])->name('history');
});
