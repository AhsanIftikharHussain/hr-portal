<?php

namespace App\Actions;

use App\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\User;

class TransitionLeaveRequest
{
    /** @param array<int, LeaveRequestStatus> $allowedStatuses */
    public function handle(
        LeaveRequest $leaveRequest,
        array $allowedStatuses,
        LeaveRequestStatus $newStatus,
        User $reviewer,
        ?string $comment,
    ): bool {
        return LeaveRequest::query()
            ->whereKey($leaveRequest)
            ->whereIn('status', array_map(
                fn (LeaveRequestStatus $status): string => $status->value,
                $allowedStatuses,
            ))
            ->update([
                'status' => $newStatus,
                'hr_comment' => $comment,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]) === 1;
    }
}
