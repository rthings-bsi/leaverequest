<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Notifications\Traits\ApproverFormatting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class LeaveApprovedEmployee extends Notification implements ShouldQueue
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

        // Prefer relation names, fallback to direct lookup in case relation not loaded
        $sup = $this->leave->supervisor?->name ?? null;
        if (! $sup && $this->leave->supervisor_id) {
            $sup = \App\Models\User::find($this->leave->supervisor_id)?->name ?? null;
        }

        $mgr = $this->leave->manager?->name ?? null;
        if (! $mgr && $this->leave->manager_id) {
            $mgr = \App\Models\User::find($this->leave->manager_id)?->name ?? null;
        }

        $byText = $this->formatByText($sup, $mgr);

        return (new MailMessage)
            ->subject('Your leave has been approved')
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line('Your leave for the period ' . ($this->leave->start_date) . ' — ' . ($this->leave->end_date) . ' has been approved ' . $byText . '.')
            ->action('View request', $url);
    }

    public function toArray($notifiable)
    {
        // reload to ensure fresh data
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

        // Resolve supervisor/manager names reliably (relation or id lookup)
        $sup = $leave->supervisor?->name ?? null;
        if (! $sup && ($leave->supervisor_id ?? null)) {
            $sup = \App\Models\User::find($leave->supervisor_id)?->name ?? null;
        }

        $mgr = $leave->manager?->name ?? null;
        if (! $mgr && ($leave->manager_id ?? null)) {
            $mgr = \App\Models\User::find($leave->manager_id)?->name ?? null;
        }

        $byText = $this->formatByText($sup, $mgr);

        return [
            'leave_id' => $leave->id,
            'employee' => $leave->user?->name,
            'leave_type' => $leave->leave_type,
            'days' => $leave->days,
            'start_date' => $sd,
            'end_date' => $ed,
            'supervisor' => $sup,
            'supervisor_id' => $leave->supervisor_id ?? null,
            'manager' => $mgr,
            'manager_id' => $leave->manager_id ?? null,
            'message' => 'Your leave has been approved ' . $byText,
            'url' => route('approvals.show', $leave->id),
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
