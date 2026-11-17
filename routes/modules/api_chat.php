<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API چت برای اپلیکیشن موبایل (احراز هویت Sanctum)
|--------------------------------------------------------------------------
*/

Route::prefix('chat')->name('api.chat.')->group(function () {
    Route::get('/{room}/messages', [ChatController::class, 'fetch'])->name('fetch');
    Route::post('/{room}/send', [ChatController::class, 'send'])->name('send');
});
