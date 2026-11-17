<?php

use App\Http\Controllers\AdminAuditController;
use App\Http\Controllers\AdminMonitoringController;
use App\Http\Controllers\AdminRoleController;
use App\Http\Controllers\AdminSettingController;
use App\Http\Controllers\AdminUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ماژول مدیریت سامانه: کاربران، نقش‌ها، لاگ، تنظیمات، پایش
|--------------------------------------------------------------------------
*/

Route::middleware('permission:users.manage')->prefix('admin/users')->name('admin.users.')->group(function () {
    Route::get('/', [AdminUserController::class, 'index'])->name('index');
    Route::get('/create', [AdminUserController::class, 'create'])->name('create');
    Route::post('/', [AdminUserController::class, 'store'])->name('store');
    Route::get('/{user}/edit', [AdminUserController::class, 'edit'])->name('edit');
    Route::post('/{user}', [AdminUserController::class, 'update'])->name('update');
    Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('destroy');
    Route::post('/assign-counselor', [AdminUserController::class, 'assignCounselor'])->name('assign-counselor');
});

Route::middleware('permission:roles.manage')->prefix('admin/roles')->name('admin.roles.')->group(function () {
    Route::get('/', [AdminRoleController::class, 'index'])->name('index');
    Route::post('/{role}', [AdminRoleController::class, 'update'])->name('update');
});

Route::middleware('permission:audit.view')->get('/admin/audit', [AdminAuditController::class, 'index'])->name('admin.audit');

Route::middleware('permission:settings.manage')->prefix('admin/settings')->name('admin.settings.')->group(function () {
    Route::get('/', [AdminSettingController::class, 'index'])->name('index');
    Route::post('/', [AdminSettingController::class, 'save'])->name('save');
});

Route::middleware('permission:monitoring.view')->get('/admin/monitoring', [AdminMonitoringController::class, 'index'])->name('admin.monitoring');
