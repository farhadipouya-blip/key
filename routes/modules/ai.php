<?php

use App\Http\Controllers\AiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ماژول دستیار هوش مصنوعی: مربی مطالعه، گزارش عملکرد، پیشنهاد پاسخ تیکت
|--------------------------------------------------------------------------
*/

Route::middleware('permission:ai.use')->prefix('ai')->name('ai.')->group(function () {
    Route::get('/', [AiController::class, 'index'])->name('index');
    Route::post('/chat', [AiController::class, 'chat'])->name('chat');
    Route::get('/report', [AiController::class, 'report'])->name('report');
    Route::post('/ticket-suggest', [AiController::class, 'ticketSuggest'])->name('ticket-suggest');
});
