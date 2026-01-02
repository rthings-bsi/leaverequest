<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Notifications\Traits\ApproverFormatting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ManagerApproved extends Notification implements ShouldQueue
{
    use Queueable;
    use ApproverFormatting;

    public LeaveRequest $leave;
    public $managerName;

    public function __construct(LeaveRequest $leave, $managerName = null)
    {
        $this->leave = $leave;
        $this->managerName = $managerName;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable)
    {
        $url = route('approvals.show', $this->leave->id);

        $mgrClean = $this->stripApproverName($this->managerName) ?? $this->managerName;
        if ($mgrClean) {
            $line = 'Manager ' . $mgrClean . ' has approved a leave request.';
        } else {
            $line = 'A manager has approved a leave request.';
        }

        return (new MailMessage)
            ->subject('Manager approved a leave')
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line($line)
            ->line('Employee: ' . ($this->leave->user->name ?? ''))
            ->action('View request', $url);
    }

    public function toArray($notifiable)
    {
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
            'department' => $leave->department,
            'leave_type' => $leave->leave_type,
            'days' => $leave->days,
            'start_date' => $sd,
            'end_date' => $ed,
            'manager' => $this->managerName,
            'message' => 'This leave was approved ' . $this->formatByText(null, $this->managerName),
            'url' => route('approvals.show', $leave->id),
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
