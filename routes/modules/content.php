<?php

use App\Http\Controllers\AdminExamController;
use App\Http\Controllers\AdminQuestionController;
use App\Http\Controllers\AdminSubjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ماژول محتوای آموزشی: درس و مبحث، بانک سؤال، آزمون‌ساز
|--------------------------------------------------------------------------
| دسترسی‌ها: subjects.manage / questions.manage / exams.manage
| (مدرس، تولیدکننده محتوا، مدیر)
*/

// ---------------- درس و مبحث (درخت درس > فصل > مبحث) ----------------
Route::middleware('permission:subjects.manage')->prefix('admin/subjects')->name('admin.subjects.')->group(function () {
    Route::get('/', [AdminSubjectController::class, 'index'])->name('index');
    Route::post('/', [AdminSubjectController::class, 'store'])->name('store');
    Route::put('/{subject}', [AdminSubjectController::class, 'update'])->name('update');
    Route::delete('/{subject}', [AdminSubjectController::class, 'destroy'])->name('destroy');
});

// ---------------- بانک سؤال ----------------
Route::middleware('permission:questions.manage')->prefix('admin/questions')->name('admin.questions.')->group(function () {
    Route::get('/', [AdminQuestionController::class, 'index'])->name('index');
    Route::get('/create', [AdminQuestionController::class, 'create'])->name('create');
    Route::post('/', [AdminQuestionController::class, 'store'])->name('store');
    Route::get('/{question}/edit', [AdminQuestionController::class, 'edit'])->name('edit');
    Route::put('/{question}', [AdminQuestionController::class, 'update'])->name('update');
    Route::delete('/{question}', [AdminQuestionController::class, 'destroy'])->name('destroy');
    Route::post('/{question}/toggle', [AdminQuestionController::class, 'toggle'])->name('toggle');
});

// ---------------- آزمون‌ساز ----------------
Route::middleware('permission:exams.manage')->prefix('admin/exams')->name('admin.exams.')->group(function () {
    Route::get('/', [AdminExamController::class, 'index'])->name('index');
    Route::get('/create', [AdminExamController::class, 'create'])->name('create');
    Route::post('/', [AdminExamController::class, 'store'])->name('store');
    Route::get('/{exam}/edit', [AdminExamController::class, 'edit'])->name('edit');
    Route::put('/{exam}', [AdminExamController::class, 'update'])->name('update');
    Route::delete('/{exam}', [AdminExamController::class, 'destroy'])->name('destroy');

    // سازنده آزمون: افزودن/حذف سؤال، ترتیب، بارم، تولید خودکار، انتشار
    Route::get('/{exam}/builder', [AdminExamController::class, 'builder'])->name('builder');
    Route::post('/{exam}/questions', [AdminExamController::class, 'attachQuestions'])->name('questions.attach');
    Route::post('/{exam}/auto-generate', [AdminExamController::class, 'autoGenerate'])->name('questions.generate');
    Route::put('/{exam}/questions/{examQuestion}', [AdminExamController::class, 'updateQuestion'])->name('questions.update');
    Route::delete('/{exam}/questions/{examQuestion}', [AdminExamController::class, 'detachQuestion'])->name('questions.detach');
    Route::post('/{exam}/status', [AdminExamController::class, 'setStatus'])->name('status');
});
