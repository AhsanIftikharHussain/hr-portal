<?php

use App\Http\Controllers\Admin\ApprovedLeaveRequestController;
use App\Http\Controllers\Admin\RejectedLeaveRequestController;
use App\Http\Controllers\Manager\AttendanceController;
use App\Http\Controllers\Manager\DashboardController;
use App\Http\Controllers\Manager\LeaveRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('manager')
    ->name('manager.')
    ->middleware(['auth', 'manager'])
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('leave', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
        Route::get('leave/{leave_request}', [LeaveRequestController::class, 'show'])->name('leave-requests.show');
        Route::post('leave/{leave_request}/approval', ApprovedLeaveRequestController::class)->name('leave-requests.approve');
        Route::post('leave/{leave_request}/rejection', RejectedLeaveRequestController::class)->name('leave-requests.reject');
    });
