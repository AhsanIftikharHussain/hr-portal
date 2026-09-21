<?php

use App\Http\Controllers\Admin\ArchivedEmployeeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\InactiveDepartmentController;
use App\Http\Controllers\Admin\InactiveJobTitleController;
use App\Http\Controllers\Admin\JobTitleController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::post('employees/{employee}/archive', [ArchivedEmployeeController::class, 'store'])
            ->name('employees.archive');
        Route::delete('employees/{employee}/archive', [ArchivedEmployeeController::class, 'destroy'])
            ->withTrashed()
            ->name('employees.restore');
        Route::resource('employees', EmployeeController::class)
            ->except('destroy')
            ->withTrashed(['show']);

        Route::prefix('settings')->group(function (): void {
            Route::post('departments/{department}/inactive', [InactiveDepartmentController::class, 'store'])
                ->name('departments.deactivate');
            Route::delete('departments/{department}/inactive', [InactiveDepartmentController::class, 'destroy'])
                ->name('departments.reactivate');
            Route::resource('departments', DepartmentController::class)->except(['show', 'destroy']);

            Route::post('job-titles/{job_title}/inactive', [InactiveJobTitleController::class, 'store'])
                ->name('job-titles.deactivate');
            Route::delete('job-titles/{job_title}/inactive', [InactiveJobTitleController::class, 'destroy'])
                ->name('job-titles.reactivate');
            Route::resource('job-titles', JobTitleController::class)
                ->parameters(['job-titles' => 'job_title'])
                ->except(['show', 'destroy']);
        });
    });
