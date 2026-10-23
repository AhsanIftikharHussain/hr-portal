<?php

use App\Http\Controllers\Employee\AttendanceCheckInController;
use App\Http\Controllers\Employee\AttendanceCheckOutController;
use App\Http\Controllers\Employee\AttendanceController;
use App\Http\Controllers\Employee\DashboardController;
use App\Http\Controllers\Employee\HolidayController;
use App\Http\Controllers\Employee\LeaveRequestController;
use App\Http\Controllers\Employee\PolicyController;
use App\Http\Controllers\Employee\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('employee')
    ->name('employee.')
    ->middleware(['auth', 'employee'])
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::resource('leave', LeaveRequestController::class)
            ->parameters(['leave' => 'leave_request'])
            ->names('leave-requests')
            ->only(['index', 'create', 'store', 'show']);
        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance/check-in', [AttendanceCheckInController::class, 'store'])->name('attendance.check-in');
        Route::post('attendance/check-out', [AttendanceCheckOutController::class, 'store'])->name('attendance.check-out');
        Route::resource('policies', PolicyController::class)->only(['index', 'show']);
        Route::get('holidays', [HolidayController::class, 'index'])->name('holidays.index');
    });
