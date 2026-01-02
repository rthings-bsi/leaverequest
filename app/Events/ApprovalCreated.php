<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Approval;
use App\Models\LeaveRequest;

class ApprovalCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Approval $approval)
    {
        // approval instance
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('leave.' . $this->approval->leave_request_id),
            new Channel('approvals'),
        ];
    }

    public function broadcastWith()
    {
        // Small aggregated stats to let clients update dashboards without an extra fetch
        try {
            $total = LeaveRequest::count();
            $pending = LeaveRequest::whereNull('final_status')->orWhere('final_status', 'pending')->count();
            $accepted = LeaveRequest::where(function ($q) {
                $q->where('final_status', 'approved')->orWhere('status', 'approved');
            })->count();
            $rejected = LeaveRequest::where(function ($q) {
                $q->where('final_status', 'rejected')->orWhere('status', 'rejected');
            })->count();

            // load the leave to include timestamps for client-side DOM updates
            $leave = LeaveRequest::find($this->approval->leave_request_id);
        } catch (\Throwable $e) {
            // if counting or loading fails, fallback to nulls so clients can decide to refetch
            $total = null;
            $pending = null;
            $accepted = null;
            $rejected = null;
            $leave = null;
        }

        // Compute remaining quota for the leave's user so clients can update UI without an extra fetch
        try {
            $quotaPerYear = config('leave.quota', 12);
            $used = 0;
            $remaining = null;
            if ($leave && $leave->user_id) {
                $used = LeaveRequest::where('user_id', $leave->user_id)
                    ->whereYear('start_date', now()->year)
                    ->where(function ($q) {
                        $q->whereIn('final_status', ['approved'])
                            ->orWhereIn('status', ['approved', 'accept', 'accepted']);
                    })->sum('days');
                $remaining = max(0, $quotaPerYear - (int) $used);
            }
        } catch (\Throwable $e) {
            $quotaPerYear = config('leave.quota', 12);
            $used = null;
            $remaining = null;
        }

        $iso = function ($d) {
            if ($d instanceof \DateTimeInterface) {
                return $d->format(\DateTime::ATOM);
            }
            if (is_numeric($d)) {
                try {
                    return \Carbon\Carbon::createFromTimestamp((int) $d)->toIso8601String();
                } catch (\Throwable $e) {
                    return null;
                }
            }
            if (is_string($d) && $d !== '') {
                try {
                    return \Carbon\Carbon::parse($d)->toIso8601String();
                } catch (\Throwable $e) {
                    return null;
                }
            }
            return null;
        };

        return [
            'id' => $this->approval->id,
            'leave_id' => $this->approval->leave_request_id,
            'approver_id' => $this->approval->approver_id,
            'approver_name' => $this->approval->approver?->name ?? null,
            'action' => $this->approval->action,
            'comment' => $this->approval->comment,
            'stage' => $this->approval->stage,
            'created_at' => $iso($this->approval->created_at),
            'total' => $total,
            'pending' => $pending,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'now' => $iso(now()),
            // leave-level timestamps
            'supervisor_approved_at' => $iso($leave?->supervisor_approved_at ?? null),
            'manager_approved_at' => $iso($leave?->manager_approved_at ?? null),
            'hr_notified_at' => $iso($leave?->hr_notified_at ?? null),
            'final_status' => $leave?->final_status ?? null,
            // quota info (may be null if computation failed)
            'quota' => $quotaPerYear ?? null,
            'used_quota' => $used !== null ? (int) $used : null,
            'remaining_quota' => $remaining !== null ? (int) $remaining : null,
        ];
    }

    public function broadcastAs()
    {
        return 'ApprovalCreated';
    }
}
