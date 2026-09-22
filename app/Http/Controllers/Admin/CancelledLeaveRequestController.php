<?php

namespace App\Http\Controllers\Admin;

use App\Actions\TransitionLeaveRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelLeaveRequestRequest;
use App\LeaveRequestStatus;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;

class CancelledLeaveRequestController extends Controller
{
    public function __invoke(CancelLeaveRequestRequest $request, LeaveRequest $leaveRequest, TransitionLeaveRequest $transition): RedirectResponse
    {
        $updated = $transition->handle(
            $leaveRequest,
            [LeaveRequestStatus::Pending, LeaveRequestStatus::Approved],
            LeaveRequestStatus::Cancelled,
            $request->user(),
            $request->string('hr_comment')->trim()->toString(),
        );

        return $updated
            ? back()->with('status', 'Leave request cancelled.')
            : back()->withErrors(['status' => 'Only pending or approved leave requests can be cancelled.']);
    }
}
