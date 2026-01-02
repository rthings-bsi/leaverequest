<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Notifications\Traits\ApproverFormatting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SupervisorApprovedForManager extends Notification implements ShouldQueue
{
    use Queueable;
    use ApproverFormatting;

    public LeaveRequest $leave;
    public $supervisorName;

    public function __construct(LeaveRequest $leave, $supervisorName = null)
    {
        $this->leave = $leave;
        $this->supervisorName = $supervisorName;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable)
    {
        $url = route('department_approval.show', $this->leave->id);

        $svClean = $this->stripApproverName($this->supervisorName) ?? $this->supervisorName;
        $line = $svClean ? ('Supervisor ' . $svClean . ' has approved a leave request and it requires your review.') : 'A supervisor has approved a leave request and it requires your review.';

        return (new MailMessage)
            ->subject('Supervisor approved a leave — awaiting your action')
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line($line)
            ->line('Employee: ' . ($this->leave->user->name ?? ''))
            ->action('Review request', $url);
    }

    public function toArray($notifiable)
    {
        $sd = null;
        $ed = null;
        try {
            if ($this->leave->start_date) $sd = \Illuminate\Support\Carbon::parse($this->leave->start_date)->toDateString();
        } catch (\Exception $e) {
            $sd = (string) $this->leave->start_date;
        }
        try {
            if ($this->leave->end_date) $ed = \Illuminate\Support\Carbon::parse($this->leave->end_date)->toDateString();
        } catch (\Exception $e) {
            $ed = (string) $this->leave->end_date;
        }

        return [
            'leave_id' => $this->leave->id,
            'employee' => $this->leave->user?->name,
            'department' => $this->leave->department,
            'leave_type' => $this->leave->leave_type,
            'days' => $this->leave->days,
            'start_date' => $sd,
            'end_date' => $ed,
            'supervisor' => $this->supervisorName,
            'message' => 'Supervisor ' . ($this->supervisorName ?? '') . ' approved this leave — awaiting your review',
            'url' => route('department_approval.show', $this->leave->id),
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
