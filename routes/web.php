<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسیرهای اصلی سامانه کلید
|--------------------------------------------------------------------------
| ماژول‌ها در پوشه routes/modules تعریف شده‌اند و اینجا بارگذاری می‌شوند.
*/

// ---------------- عمومی ----------------
Route::get('/', function () {
    $latestArticles = \App\Models\Article::query()->where('status', 'published')->where('is_public', true)
        ->latest('published_at')->take(3)->get();
    $courses = \App\Models\Course::query()->where('is_published', true)->take(3)->get();

    return view('public.home', compact('latestArticles', 'courses'));
})->name('public.home');

// ---------------- احراز هویت ----------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');

    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendReset'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
});

Route::middleware(['auth', 'active.user', 'last.seen'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // داشبورد و پروفایل
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])->name('admin.dashboard');
    Route::get('/dashboard/student', [DashboardController::class, 'student'])->name('student.dashboard');
    Route::get('/dashboard/counselor', [DashboardController::class, 'counselor'])->name('counselor.dashboard');
    Route::get('/dashboard/teacher', [DashboardController::class, 'teacher'])->name('teacher.dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/notifications/read-all', [ProfileController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [ProfileController::class, 'readOne'])->name('notifications.read');

    // بارگذاری ماژول‌ها
    // public.php فقط برای مهمان جداگانه بارگذاری می‌شود و api_chat.php مخصوص routes/api.php است
    foreach (glob(__DIR__ . '/modules/*.php') ?: [] as $moduleFile) {
        if (in_array(basename($moduleFile), ['public.php', 'api_chat.php'], true)) {
            continue;
        }
        require $moduleFile;
    }
});

// استعلام عمومی گواهینامه (بدون نیاز به ورود)
require __DIR__ . '/modules/public.php';
