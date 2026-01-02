<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewLeaveForApprover extends Notification implements ShouldQueue
{
    use Queueable;

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
        $url = route('department_approval.show', $this->leave->id);

        return (new MailMessage)
            ->subject('Leave awaiting your approval')
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line('A leave request requires your review for department: ' . ($this->leave->department ?? ''))
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
            'message' => 'New leave awaiting your approval',
            'url' => route('department_approval.show', $this->leave->id),
            // indicate which approval roles are required for this leave (helps the UI show a tailored label)
            'required_approvals' => $this->determineRequiredApprovals(),
        ];
    }

    protected function determineRequiredApprovals()
    {
        try {
            $requester = $this->leave->user ?? null;
            if ($requester && method_exists($requester, 'hasAnyRole')) {
                try {
                    if ($requester->hasAnyRole(['manager'])) {
                        return ['hod'];
                    }
                    if ($requester->hasAnyRole(['supervisor'])) {
                        return ['manager'];
                    }
                } catch (\Throwable $e) {
                    // ignore and fallthrough to default
                }
            }
        } catch (\Exception $e) {
            // ignore
        }

        // default: supervisor + manager required
        return ['supervisor', 'manager'];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
