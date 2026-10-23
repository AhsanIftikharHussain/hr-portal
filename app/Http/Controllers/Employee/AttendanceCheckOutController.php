<?php

namespace App\Http\Controllers\Employee;

use App\AttendanceSource;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceCheckOutController extends Controller
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
        $businessDate = $businessNow->toDateString();
        $updated = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $businessDate)
            ->where('source', AttendanceSource::EmployeePortal)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->update(['check_out_at' => $businessNow]);

        if ($updated === 1) {
            return redirect()->route('employee.dashboard')
                ->with('success', 'Checked out successfully at '.$businessNow->format('h:i A').'.');
        }

        $attendanceRecord = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $businessDate)
            ->first();

        $message = match (true) {
            $attendanceRecord === null => 'You must check in before checking out.',
            $attendanceRecord->source !== AttendanceSource::EmployeePortal => 'Attendance has been recorded by HR and can only be changed by HR.',
            $attendanceRecord->check_in_at === null => 'You must check in before checking out.',
            $attendanceRecord->check_out_at !== null => 'You have already checked out for today.',
            default => 'Attendance could not be checked out safely. Please contact HR.',
        };

        return redirect()->route('employee.dashboard')->withErrors(['attendance' => $message]);
    }
}
