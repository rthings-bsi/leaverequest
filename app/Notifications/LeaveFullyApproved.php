<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Notifications\Traits\ApproverFormatting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class LeaveFullyApproved extends Notification implements ShouldQueue
{
    use Queueable;
    use ApproverFormatting;

    public LeaveRequest $leave;

    public function __construct(LeaveRequest $leave)
    {
        $this->leave = $leave;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable)
    {
        $url = route('approvals.show', $this->leave->id);

        return (new MailMessage)
            ->subject('Leave fully approved')
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line('A leave request was fully approved for ' . ($this->leave->user->name ?? ''))
            ->line('Period: ' . $this->leave->start_date . ' — ' . $this->leave->end_date)
            ->action('View request', $url);
    }

    public function toArray($notifiable)
    {
        // reload leave to ensure we include the latest DB values
        $leave = \App\Models\LeaveRequest::find($this->leave->id) ?? $this->leave;

        $sd = null;
        $ed = null;
        try {
            if ($leave->start_date) $sd = \Illuminate\Support\Carbon::parse($leave->start_date)->toDateString();
        } catch (\Exception $e) {
            $sd = (string) $leave->start_date;
        }
        try {
            if ($leave->end_date) $ed = \Illuminate\Support\Carbon::parse($leave->end_date)->toDateString();
        } catch (\Exception $e) {
            $ed = (string) $leave->end_date;
        }

        return [
            'leave_id' => $leave->id,
            'employee' => $leave->user?->name,
            'leave_type' => $leave->leave_type,
            'days' => $leave->days,
            'start_date' => $sd,
            'end_date' => $ed,
            'manager' => $leave->manager?->name ?? null,
            'manager_id' => $leave->manager?->id ?? null,
            'message' => 'Leave fully approved ' . $this->formatByText(null, $leave->manager?->name ?? null),
            'url' => route('approvals.show', $leave->id),
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
