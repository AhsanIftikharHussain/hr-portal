<?php

namespace App\Http\Controllers\Employee;

use App\AttendanceStatus;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee()->first();

        if ($employee === null) {
            return view('employee.attendance.index', ['employee' => null]);
        }

        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', Rule::enum(AttendanceStatus::class)],
        ]);
        $month = $request->string('month')->toString() ?: now()->format('Y-m');
        [$year, $monthNumber] = array_map('intval', explode('-', $month));

        $attendanceRecords = $employee->attendanceRecords()
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $monthNumber)
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('employee.attendance.index', [
            'employee' => $employee,
            'attendanceRecords' => $attendanceRecords,
            'month' => $month,
            'statuses' => AttendanceStatus::cases(),
        ]);
    }
}
