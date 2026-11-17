<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ماژول چت آنلاین: اتاق‌های خصوصی/گروهی/کلاس + نظرسنجی + وایت‌برد
|--------------------------------------------------------------------------
*/

Route::middleware('permission:chat.use')->prefix('chat')->name('chat.')->group(function () {
    Route::get('/', [ChatController::class, 'index'])->name('index');
    Route::post('/group', [ChatController::class, 'groupStore'])->name('group.store');
    Route::post('/private', [ChatController::class, 'privateStore'])->name('private.store');
    Route::get('/{room}', [ChatController::class, 'room'])->name('room');
    Route::post('/{room}/send', [ChatController::class, 'send'])->name('send');
    Route::get('/{room}/messages', [ChatController::class, 'fetch'])->name('fetch');
    Route::post('/{room}/vote/{message}', [ChatController::class, 'vote'])->name('vote');
});
