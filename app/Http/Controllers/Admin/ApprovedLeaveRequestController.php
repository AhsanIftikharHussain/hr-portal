<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SendLeaveDecisionNotification;
use App\Actions\TransitionLeaveRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewLeaveRequestRequest;
use App\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Notifications\LeaveRequestApproved;
use Illuminate\Http\RedirectResponse;

class ApprovedLeaveRequestController extends Controller
{
    public function __invoke(
        ReviewLeaveRequestRequest $request,
        LeaveRequest $leaveRequest,
        TransitionLeaveRequest $transition,
        SendLeaveDecisionNotification $sendNotification,
    ): RedirectResponse {
        $updated = $transition->handle(
            $leaveRequest,
            [LeaveRequestStatus::Pending],
            LeaveRequestStatus::Approved,
            $request->user(),
            $request->string('hr_comment')->trim()->toString() ?: null,
        );

        if (! $updated) {
            return back()->withErrors(['status' => 'This leave request has already been reviewed and can no longer be approved.']);
        }

        $sendNotification->handle($leaveRequest, new LeaveRequestApproved($leaveRequest));

        return back()->with('status', 'Leave request approved.');
    }
}
