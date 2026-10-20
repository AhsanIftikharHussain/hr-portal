<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\LeaveRequestStatus;
use App\Models\Holiday;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $employee = $request->user()->employee()
            ->with(['department:id,name', 'jobTitle:id,name', 'reportingManager:id,full_name,deleted_at'])
            ->first();

        if ($employee === null) {
            return view('employee.dashboard', ['employee' => null]);
        }

        return view('employee.dashboard', [
            'employee' => $employee,
            'pendingLeaveCount' => $employee->leaveRequests()->where('status', LeaveRequestStatus::Pending)->count(),
            'approvedLeaveThisYearCount' => $employee->leaveRequests()
                ->where('status', LeaveRequestStatus::Approved)
                ->whereYear('start_date', now()->year)
                ->count(),
            'todayAttendance' => $employee->attendanceRecords()
                ->whereDate('attendance_date', today())
                ->first(),
            'upcomingHoliday' => Holiday::query()
                ->where('is_active', true)
                ->whereDate('holiday_date', '>=', today())
                ->orderBy('holiday_date')
                ->orderBy('id')
                ->first(),
            'unreadNotificationCount' => $request->user()->unreadNotifications()->count(),
            'recentLeaveRequests' => $employee->leaveRequests()
                ->with('leaveType:id,name')
                ->orderByDesc('requested_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
        ]);
    }
}
