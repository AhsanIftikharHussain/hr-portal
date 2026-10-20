<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestRejected extends Notification
{
    public function __construct(public LeaveRequest $leaveRequest) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(config('app.name').' — Leave request rejected')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->message())
            ->line('Please contact HR if you need further information.')
            ->action('View leave request', $this->destinationUrl($notifiable))
            ->line('This is an automated notification from '.config('app.name').'.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Leave request rejected',
            'message' => $this->message(),
            'category' => 'leave-rejected',
            'url' => $this->destinationUrl($notifiable),
        ];
    }

    private function destinationUrl(object $notifiable): string
    {
        if ($notifiable instanceof User
            && $notifiable->hasRole(Role::EMPLOYEE)
            && $notifiable->employee()->whereKey($this->leaveRequest->employee_id)->exists()) {
            return route('employee.leave-requests.show', $this->leaveRequest);
        }

        return route('notifications.index');
    }

    private function message(): string
    {
        return sprintf(
            'Your %s request from %s to %s has been rejected.',
            $this->leaveRequest->leaveType->name,
            $this->leaveRequest->start_date->format('d M Y'),
            $this->leaveRequest->end_date->format('d M Y'),
        );
    }
}
