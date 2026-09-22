<?php

namespace App\Http\Controllers\Admin;

use App\Actions\TransitionLeaveRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewLeaveRequestRequest;
use App\LeaveRequestStatus;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;

class ApprovedLeaveRequestController extends Controller
{
    public function __invoke(ReviewLeaveRequestRequest $request, LeaveRequest $leaveRequest, TransitionLeaveRequest $transition): RedirectResponse
    {
        $updated = $transition->handle(
            $leaveRequest,
            [LeaveRequestStatus::Pending],
            LeaveRequestStatus::Approved,
            $request->user(),
            $request->string('hr_comment')->trim()->toString() ?: null,
        );

        return $updated
            ? back()->with('status', 'Leave request approved.')
            : back()->withErrors(['status' => 'This leave request has already been reviewed and can no longer be approved.']);
    }
}
