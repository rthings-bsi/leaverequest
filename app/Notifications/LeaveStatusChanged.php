<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use App\Models\LeaveRequest;
use App\Notifications\Traits\ApproverFormatting;

class LeaveStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;
    use ApproverFormatting;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected LeaveRequest $leave)
    {
        // LeaveRequest instance is stored in $this->leave
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("Leave Request Status: {$this->leave->status}")
            ->line("Your leave request from {$this->leave->start_date} to {$this->leave->end_date} has status: {$this->leave->status}.")
            ->when($this->leave->admin_comment, fn($message) => $message->line("Comment: {$this->leave->admin_comment}"))
            ->action('View request', route('approvals.show', $this->leave->id));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Reload the leave from DB to ensure we have the latest status after multi-stage updates
        $leave = \App\Models\LeaveRequest::find($this->leave->id) ?? $this->leave;

        // Determine approver (manager/supervisor/legacy approver)
        $approverName = null;
        $approverRole = null;
        if ($leave->approver) {
            $approverName = $leave->approver->name;
            $approverRole = 'approver';
        } elseif ($leave->manager) {
            $approverName = $leave->manager->name;
            $approverRole = 'manager';
        } elseif (property_exists($leave, 'supervisor') && $leave->supervisor) {
            $approverName = $leave->supervisor->name;
            $approverRole = 'supervisor';
        }

        // prefer final_status (set by manager) over raw status column
        $status = $leave->final_status ?? $leave->status ?? null;

        $message = "Leave request status changed";
        if ($status === 'approved' || $status === 'approved') {
            if ($approverName) {
                $roleLabel = $approverRole ? ucfirst($approverRole) : 'Approver';
                $cleanApprover = $this->stripApproverName($approverName) ?? $approverName;
                $message = "Your leave request has been approved by {$roleLabel} {$cleanApprover}";
            } else {
                $message = "Your leave request has been approved";
            }
        } elseif ($status === 'rejected') {
            if ($approverName) {
                $roleLabel = $approverRole ? ucfirst($approverRole) : 'Approver';
                $cleanApprover = $this->stripApproverName($approverName) ?? $approverName;
                $message = "Your leave request has been rejected by {$roleLabel} {$cleanApprover}";
            } else {
                $message = "Your leave request has been rejected";
            }
        }

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
            'id' => $this->leave->id,
            'status' => $status,
            'start_date' => $sd,
            'end_date' => $ed,
            'admin_comment' => $this->leave->admin_comment,
            'approver' => $approverName,
            'approver_role' => $approverRole,
            'message' => $message,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
