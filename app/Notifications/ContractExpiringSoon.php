<?php

namespace App\Notifications;

use App\Models\EmployeeContract;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractExpiringSoon extends Notification
{
    public function __construct(public EmployeeContract $contract) {}

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
            ->subject(config('app.name').' — Contract expiring soon')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->message())
            ->action('Review contract', route('admin.contracts.edit', $this->contract))
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
            'title' => 'Contract expiring soon',
            'message' => $this->message(),
            'category' => 'contract-expiring-soon',
            'url' => route('admin.contracts.edit', $this->contract),
        ];
    }

    private function message(): string
    {
        $daysRemaining = (int) today()->diffInDays($this->contract->end_date, false);

        return sprintf(
            "%s's %s contract ends on %s (%d %s remaining).",
            $this->contract->employee->full_name,
            $this->contract->contract_type->label(),
            $this->contract->end_date->format('d M Y'),
            $daysRemaining,
            str('day')->plural($daysRemaining),
        );
    }
}
