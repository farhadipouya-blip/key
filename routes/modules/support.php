<?php

use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ماژول تیکت پشتیبانی: تیکت‌های کاربر + میز پشتیبانی کارشناسان
|--------------------------------------------------------------------------
*/

Route::middleware('permission:tickets.view')->prefix('tickets')->name('tickets.')->group(function () {
    Route::get('/', [TicketController::class, 'index'])->name('index');
    Route::get('/create', [TicketController::class, 'create'])->name('create');
    Route::post('/', [TicketController::class, 'store'])->name('store');
    Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
    Route::post('/{ticket}/reply', [TicketController::class, 'reply'])->name('reply');
    Route::post('/{ticket}/close', [TicketController::class, 'close'])->name('close');
});

Route::middleware('permission:tickets.support')->prefix('support/tickets')->name('support.tickets.')->group(function () {
    Route::get('/', [SupportTicketController::class, 'index'])->name('index');
    Route::post('/{ticket}/assign', [SupportTicketController::class, 'assign'])->name('assign');
});
