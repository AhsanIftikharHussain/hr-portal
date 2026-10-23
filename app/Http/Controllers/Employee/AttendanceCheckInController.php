<?php

namespace App\Http\Controllers\Employee;

use App\AttendanceSource;
use App\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceCheckInController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee()->first();

        if ($employee === null) {
            return redirect()->route('employee.dashboard')->withErrors([
                'attendance' => 'Your account has not yet been linked to an employee record. Please contact HR.',
            ]);
        }

        $businessNow = CarbonImmutable::now(config('app.business_timezone'));

        try {
            AttendanceRecord::query()->create([
                'employee_id' => $employee->id,
                'attendance_date' => $businessNow->toDateString(),
                'check_in_at' => $businessNow,
                'status' => AttendanceStatus::Present,
                'source' => AttendanceSource::EmployeePortal,
                'recorded_by' => $request->user()->id,
            ]);
        } catch (UniqueConstraintViolationException) {
            return redirect()->route('employee.dashboard')->withErrors([
                'attendance' => 'Attendance has already been recorded for today.',
            ]);
        }

        return redirect()->route('employee.dashboard')
            ->with('success', 'Checked in successfully at '.$businessNow->format('h:i A').'.');
    }
}
