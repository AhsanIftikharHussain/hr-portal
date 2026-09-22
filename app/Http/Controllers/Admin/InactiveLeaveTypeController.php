<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class InactiveLeaveTypeController extends Controller
{
    public function store(LeaveType $leaveType): RedirectResponse
    {
        Gate::authorize('update', $leaveType);
        $leaveType->update(['is_active' => false]);

        return back()->with('status', 'Leave type deactivated. Existing leave history was preserved.');
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        Gate::authorize('update', $leaveType);
        $leaveType->update(['is_active' => true]);

        return back()->with('status', 'Leave type reactivated.');
    }
}
