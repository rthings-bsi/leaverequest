<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\LeaveRequest;

class LeaveUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public LeaveRequest $leave) {}

    public function broadcastOn()
    {
        return [
            new PrivateChannel('leave.' . $this->leave->id),
            new Channel('approvals'),
        ];
    }

    public function broadcastWith()
    {
        try {
            $total = LeaveRequest::count();
            $pending = LeaveRequest::whereNull('final_status')->orWhere('final_status', 'pending')->count();
            $accepted = LeaveRequest::where(function ($q) {
                $q->where('final_status', 'approved')->orWhere('status', 'approved');
            })->count();
            $rejected = LeaveRequest::where(function ($q) {
                $q->where('final_status', 'rejected')->orWhere('status', 'rejected');
            })->count();

            $iso = function ($value) {
                if ($value instanceof \DateTimeInterface) {
                    return \Illuminate\Support\Carbon::instance($value)->toIso8601String();
                }

                if (is_numeric($value)) {
                    try {
                        return \Illuminate\Support\Carbon::createFromTimestamp($value)->toIso8601String();
                    } catch (\Throwable $e) {
                        return null;
                    }
                }

                if (is_string($value) && $value !== '') {
                    try {
                        return \Illuminate\Support\Carbon::parse($value)->toIso8601String();
                    } catch (\Throwable $e) {
                        return null;
                    }
                }

                return null;
            };

            return [
                'event' => 'LeaveUpdated',
                'leave' => [
                    'id' => $this->leave->id,
                    'user_name' => $this->leave->user?->name ?? null,
                    'department' => $this->leave->department ?? null,
                    'created_at' => $iso($this->leave->created_at),
                    'updated_at' => $iso($this->leave->updated_at),
                    'supervisor_approved_at' => $iso($this->leave->supervisor_approved_at),
                    'manager_approved_at' => $iso($this->leave->manager_approved_at),
                    'hr_notified_at' => $iso($this->leave->hr_notified_at),
                    'final_status' => $this->leave->final_status ?? null,
                ],
                'total' => $total,
                'pending' => $pending,
                'accepted' => $accepted,
                'rejected' => $rejected,
                'now' => $iso(now()),
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('LeaveUpdated broadcast failed: ' . $e->getMessage(), ['exception' => $e]);
            return [
                'event' => 'LeaveUpdated',
                'leave' => [
                    'id' => $this->leave->id,
                ],
            ];
        }
    }

    public function broadcastAs()
    {
        return 'LeaveUpdated';
    }
}
