<?php

namespace App\Actions;

use App\Models\LeaveRequest;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

class SendLeaveDecisionNotification
{
    public function handle(LeaveRequest $leaveRequest, Notification $notification): void
    {
        $leaveRequest->loadMissing(['employee.user', 'leaveType']);
        $recipient = $leaveRequest->employee?->user;

        if ($recipient === null || ! $recipient->is_active) {
            return;
        }

        foreach (['database', 'mail'] as $channel) {
            try {
                NotificationFacade::sendNow($recipient, $notification, [$channel]);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
