<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SendLeaveDecisionNotification;
use App\Actions\TransitionLeaveRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectLeaveRequestRequest;
use App\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Notifications\LeaveRequestRejected;
use Illuminate\Http\RedirectResponse;

class RejectedLeaveRequestController extends Controller
{
    public function __invoke(
        RejectLeaveRequestRequest $request,
        LeaveRequest $leaveRequest,
        TransitionLeaveRequest $transition,
        SendLeaveDecisionNotification $sendNotification,
    ): RedirectResponse {
        $updated = $transition->handle(
            $leaveRequest,
            [LeaveRequestStatus::Pending],
            LeaveRequestStatus::Rejected,
            $request->user(),
            $request->string('hr_comment')->trim()->toString(),
        );

        if (! $updated) {
            return back()->withErrors(['status' => 'This leave request has already been reviewed and can no longer be rejected.']);
        }

        $sendNotification->handle($leaveRequest, new LeaveRequestRejected($leaveRequest));

        return back()->with('status', 'Leave request rejected.');
    }
}
