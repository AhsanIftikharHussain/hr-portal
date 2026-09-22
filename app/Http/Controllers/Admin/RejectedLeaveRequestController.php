<?php

namespace App\Http\Controllers\Admin;

use App\Actions\TransitionLeaveRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectLeaveRequestRequest;
use App\LeaveRequestStatus;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;

class RejectedLeaveRequestController extends Controller
{
    public function __invoke(RejectLeaveRequestRequest $request, LeaveRequest $leaveRequest, TransitionLeaveRequest $transition): RedirectResponse
    {
        $updated = $transition->handle(
            $leaveRequest,
            [LeaveRequestStatus::Pending],
            LeaveRequestStatus::Rejected,
            $request->user(),
            $request->string('hr_comment')->trim()->toString(),
        );

        return $updated
            ? back()->with('status', 'Leave request rejected.')
            : back()->withErrors(['status' => 'This leave request has already been reviewed and can no longer be rejected.']);
    }
}
