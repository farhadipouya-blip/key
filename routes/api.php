<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes سامانه کلید (KEY)
|--------------------------------------------------------------------------
| مسیرهای RESTful برای توسعه‌های آینده و اپلیکیشن‌های موبایل.
| احراز هویت با Sanctum Token انجام می‌شود.
*/

Route::get('/health', fn () => response()->json([
    'app' => 'KEY — سامانه جامع آموزشی',
    'status' => 'ok',
    'time' => now()->toIso8601String(),
]));

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', fn (Request $request) => $request->user()->only([
        'id', 'name', 'email', 'phone', 'grade', 'field_group',
    ]));

    // API ماژول‌ها — در فایل‌های جداگانه
    foreach (glob(__DIR__ . '/modules/api_*.php') ?: [] as $apiFile) {
        require $apiFile;
    }
});
