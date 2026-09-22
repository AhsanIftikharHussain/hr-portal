<?php

use App\Http\Controllers\Admin\ApprovedLeaveRequestController;
use App\Http\Controllers\Admin\ArchivedEmployeeController;
use App\Http\Controllers\Admin\AttendanceRecordController;
use App\Http\Controllers\Admin\CancelledLeaveRequestController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\InactiveDepartmentController;
use App\Http\Controllers\Admin\InactiveJobTitleController;
use App\Http\Controllers\Admin\InactiveLeaveTypeController;
use App\Http\Controllers\Admin\JobTitleController;
use App\Http\Controllers\Admin\LeaveRequestController;
use App\Http\Controllers\Admin\LeaveTypeController;
use App\Http\Controllers\Admin\MonthlyAttendanceController;
use App\Http\Controllers\Admin\RejectedLeaveRequestController;
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

        Route::get('attendance/monthly', MonthlyAttendanceController::class)
            ->name('attendance.monthly');
        Route::resource('attendance', AttendanceRecordController::class)
            ->parameters(['attendance' => 'attendance_record'])
            ->only(['index', 'create', 'store', 'edit', 'update']);

        Route::post('leave/{leave_request}/approval', ApprovedLeaveRequestController::class)
            ->name('leave-requests.approve');
        Route::post('leave/{leave_request}/rejection', RejectedLeaveRequestController::class)
            ->name('leave-requests.reject');
        Route::post('leave/{leave_request}/cancellation', CancelledLeaveRequestController::class)
            ->name('leave-requests.cancel');
        Route::resource('leave', LeaveRequestController::class)
            ->parameters(['leave' => 'leave_request'])
            ->names('leave-requests')
            ->only(['index', 'create', 'store', 'show']);

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

            Route::post('leave-types/{leave_type}/inactive', [InactiveLeaveTypeController::class, 'store'])
                ->name('leave-types.deactivate');
            Route::delete('leave-types/{leave_type}/inactive', [InactiveLeaveTypeController::class, 'destroy'])
                ->name('leave-types.reactivate');
            Route::resource('leave-types', LeaveTypeController::class)
                ->parameters(['leave-types' => 'leave_type'])
                ->except(['show', 'destroy']);
        });
    });
