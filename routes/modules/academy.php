<?php

use App\Http\Controllers\AcademyAdminController;
use App\Http\Controllers\AcademyClassController;
use App\Http\Controllers\AcademyController;
use App\Http\Controllers\ArticleAdminController;
use App\Http\Controllers\ArticlePublicController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\CertificateAdminController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\TeacherController;

/*
|--------------------------------------------------------------------------
| ماژول آکادمی: دوره‌ها، درس‌ها، کلاس آنلاین، تکالیف، مقالات و گواهی‌نامه
|--------------------------------------------------------------------------
| نکته: مسیرهای عمومی مقالات با withoutMiddleware از احراز هویت گروه خارج
| شده‌اند تا بدون ورود نیز قابل مشاهده باشند (layouts.public).
*/

// ---------------- دانشجو / یادگیری ----------------
Route::middleware('permission:academy.learn')->prefix('academy')->name('academy.')->group(function () {
    Route::get('/', [AcademyController::class, 'coursesIndex'])->name('courses.index');
    Route::get('/my-courses', [AcademyController::class, 'myCourses'])->name('my-courses');
    Route::get('/classes', [AcademyController::class, 'classes'])->name('classes');
    Route::post('/classes/{class}/join', [AcademyController::class, 'joinClass'])->name('classes.join');
    Route::get('/my-assignments', [AcademyController::class, 'myAssignments'])->name('my-assignments');

    Route::get('/courses/{course}', [AcademyController::class, 'coursesShow'])->name('courses.show');
    Route::post('/courses/{course}/enroll', [AcademyController::class, 'enroll'])->name('enroll');
    Route::get('/courses/{course}/lessons/{lesson}', [AcademyController::class, 'lesson'])->name('lesson');
    Route::post('/courses/{course}/lessons/{lesson}/complete', [AcademyController::class, 'lessonComplete'])->name('lesson.complete');

    Route::get('/assignments/{assignment}', [AcademyController::class, 'assignmentShow'])->name('assignment.show');
    Route::post('/assignments/{assignment}/submit', [AcademyController::class, 'assignmentSubmit'])->name('assignment.submit');
});

// ---------------- گواهی‌نامه‌های من ----------------
Route::middleware('permission:certificates.view')->prefix('certificates')->name('certificates.')->group(function () {
    Route::get('/', [CertificateController::class, 'index'])->name('index');
    Route::get('/{certificate}', [CertificateController::class, 'show'])->name('show');
});

// ---------------- مدیریت آکادمی ----------------
Route::middleware('permission:academy.manage')->prefix('academy-admin')->name('admin.')->group(function () {
    // دوره‌ها
    Route::get('/courses', [AcademyAdminController::class, 'coursesIndex'])->name('courses.index');
    Route::get('/courses/create', [AcademyAdminController::class, 'coursesCreate'])->name('courses.create');
    Route::post('/courses', [AcademyAdminController::class, 'coursesStore'])->name('courses.store');
    Route::get('/courses/{course}/edit', [AcademyAdminController::class, 'coursesEdit'])->name('courses.edit');
    Route::put('/courses/{course}', [AcademyAdminController::class, 'coursesUpdate'])->name('courses.update');
    Route::delete('/courses/{course}', [AcademyAdminController::class, 'coursesDestroy'])->name('courses.destroy');

    // درس‌ها (داخل صفحه ویرایش دوره)
    Route::post('/courses/{course}/lessons', [AcademyAdminController::class, 'lessonStore'])->name('course.lesson.store');
    Route::put('/courses/{course}/lessons/{lesson}', [AcademyAdminController::class, 'lessonUpdate'])->name('course.lesson.update');
    Route::delete('/courses/{course}/lessons/{lesson}', [AcademyAdminController::class, 'lessonDestroy'])->name('course.lesson.destroy');

    // کلاس‌های آنلاین
    Route::get('/classes', [AcademyClassController::class, 'index'])->name('classes.index');
    Route::get('/classes/create', [AcademyClassController::class, 'create'])->name('classes.create');
    Route::post('/classes', [AcademyClassController::class, 'store'])->name('classes.store');
    Route::get('/classes/{onlineClass}/edit', [AcademyClassController::class, 'edit'])->name('classes.edit');
    Route::put('/classes/{onlineClass}', [AcademyClassController::class, 'update'])->name('classes.update');
    Route::delete('/classes/{onlineClass}', [AcademyClassController::class, 'destroy'])->name('classes.destroy');

    // تکالیف
    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/assignments/create', [AssignmentController::class, 'create'])->name('assignments.create');
    Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::get('/assignments/{assignment}/edit', [AssignmentController::class, 'edit'])->name('assignments.edit');
    Route::put('/assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

    // مقالات
    Route::get('/articles', [ArticleAdminController::class, 'index'])->name('articles.index');
    Route::get('/articles/create', [ArticleAdminController::class, 'create'])->name('articles.create');
    Route::post('/articles', [ArticleAdminController::class, 'store'])->name('articles.store');
    Route::get('/articles/{article}/edit', [ArticleAdminController::class, 'edit'])->name('articles.edit');
    Route::put('/articles/{article}', [ArticleAdminController::class, 'update'])->name('articles.update');
    Route::delete('/articles/{article}', [ArticleAdminController::class, 'destroy'])->name('articles.destroy');
});

// ---------------- مدیریت گواهی‌نامه‌ها ----------------
Route::middleware('permission:certificates.issue')->prefix('academy-admin/certificates')->name('admin.certificates.')->group(function () {
    Route::get('/', [CertificateAdminController::class, 'index'])->name('index');
    Route::post('/issue', [CertificateAdminController::class, 'issue'])->name('issue');
    Route::post('/{certificate}/revoke', [CertificateAdminController::class, 'revoke'])->name('revoke');
});

// ---------------- پنل مدرس ----------------
Route::middleware('permission:academy.teach')->prefix('academy-teacher')->name('teacher.')->group(function () {
    Route::get('/courses', [TeacherController::class, 'courses'])->name('courses');
    Route::get('/assignments', [TeacherController::class, 'assignments'])->name('assignments');
    Route::post('/submissions/{submission}/grade', [TeacherController::class, 'grade'])->name('assignment.grade');
    Route::get('/classes', [TeacherController::class, 'classes'])->name('classes');
    Route::get('/gradebook', [TeacherController::class, 'gradebook'])->name('gradebook');
});

// ---------------- مقالات عمومی (بدون نیاز به ورود) ----------------
Route::prefix('articles')->name('articles.public.')->withoutMiddleware(['auth', 'active.user', 'last.seen'])->group(function () {
    Route::get('/', [ArticlePublicController::class, 'index'])->name('index');
    Route::get('/{article:slug}', [ArticlePublicController::class, 'show'])->name('show');
    Route::post('/{article:slug}/comments', [ArticlePublicController::class, 'comment'])->name('comment');
});
