<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewLeaveSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public LeaveRequest $leave;

    /**
     * Create a new notification instance.
     */
    public function __construct(LeaveRequest $leave)
    {
        $this->leave = $leave;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $url = route('approvals.show', $this->leave->id);

        return (new MailMessage)
            ->subject('New leave request submitted')
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line('A new leave request has been submitted by ' . ($this->leave->user->name ?? ''))
            ->line('Leave type: ' . $this->leave->leave_type)
            ->line('Period: ' . $this->leave->start_date . ' — ' . $this->leave->end_date)
            ->action('Review request', $url)
            ->line('You can review and approve or reject the request from the admin panel.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'leave_id' => $this->leave->id,
            'user_id' => $this->leave->user_id,
            'leave_type' => $this->leave->leave_type,
            'start_date' => $this->leave->start_date,
            'end_date' => $this->leave->end_date,
            'message' => 'New leave request submitted',
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
