<?php

use App\Http\Controllers\PlanningController;

/*
|--------------------------------------------------------------------------
| ماژول برنامه مطالعاتی
|--------------------------------------------------------------------------
*/

Route::middleware('permission:plans.view')->prefix('planning')->name('plan.')->group(function () {
    Route::get('/', [PlanningController::class, 'index'])->name('index');
    Route::post('/', [PlanningController::class, 'store'])->name('store');
    Route::post('/session', [PlanningController::class, 'storeSession'])->name('session.store');
    Route::post('/goal', [PlanningController::class, 'storeGoal'])->name('goal.store');
    Route::get('/report', [PlanningController::class, 'report'])->name('report');

    Route::prefix('{plan}')->scopeBindings()->group(function () {
        Route::post('/items', [PlanningController::class, 'storeItem'])->name('item.store');
        Route::put('/items/{item}', [PlanningController::class, 'updateItem'])->name('item.update');
        Route::delete('/items/{item}', [PlanningController::class, 'destroyItem'])->name('item.destroy');
        Route::post('/replan', [PlanningController::class, 'replan'])->name('replan');
    });
});
