<?php

use App\Http\Controllers\CounselorController;

/*
|--------------------------------------------------------------------------
| ماژول پنل مشاور و پرونده جامع دانشجو (Student 360)
|--------------------------------------------------------------------------
| مجوز پنل: counselor.panel — عملیات تخصیص دانشجو: users.manage (مدیریت)
| توجه: در این RBAC نقش‌های admin/superadmin همه مجوزها (شامل counselor.panel)
| را دارند؛ بنابراین «counselor.panel یا users.manage» با همان middleware
| پوشش داده می‌شود و کنترل مالکیت دقیق در کنترلر انجام می‌شود.
*/

Route::middleware('permission:counselor.panel')->prefix('counselor')->name('counselor.')->group(function () {
    // دانشجویان تخصیص‌یافته
    Route::get('students', [CounselorController::class, 'students'])->name('students');

    // پرونده جامع دانشجو (همان Student 360)
    Route::get('students/{student}', [CounselorController::class, 'show'])->name('student');

    // جلسات مشاوره
    Route::get('sessions', [CounselorController::class, 'sessions'])->name('sessions');
    Route::post('sessions', [CounselorController::class, 'sessionsStore'])->name('sessions.store');
    Route::put('sessions/{session}', [CounselorController::class, 'sessionsUpdate'])->name('sessions.update');

    // پیگیری‌ها
    Route::get('followups', [CounselorController::class, 'followups'])->name('followups');
    Route::post('followups', [CounselorController::class, 'followupsStore'])->name('followups.store');
    Route::post('followups/{followup}/done', [CounselorController::class, 'followupsDone'])->name('followups.done');

    // تخصیص دانشجو (فقط مدیر — users.manage) و آرشیو
    Route::post('students/assign', [CounselorController::class, 'assign'])
        ->middleware('permission:users.manage')->name('students.assign');
    Route::post('students/{student}/archive', [CounselorController::class, 'archive'])->name('students.archive');
});

// مسیر مستقیم مدیریت به پرونده جامع دانشجو (Student 360)
// مجوز: counselor.panel یا users.manage — هر دو توسط کنترلر بررسی می‌شوند
Route::get('students/{user}/360', [CounselorController::class, 'direct'])
    ->middleware('permission:counselor.panel')
    ->name('student.360');
