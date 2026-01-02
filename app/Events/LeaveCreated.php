<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\LeaveRequest;

class LeaveCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public LeaveRequest $leave) {}

    public function broadcastOn()
    {
        return [
            new Channel('approvals'),
        ];
    }

    public function broadcastWith()
    {
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

        try {
            $total = LeaveRequest::count();
            $pending = LeaveRequest::whereNull('final_status')->orWhere('final_status', 'pending')->count();
            $accepted = LeaveRequest::where(function ($q) {
                $q->where('final_status', 'approved')->orWhere('status', 'approved');
            })->count();
            $rejected = LeaveRequest::where(function ($q) {
                $q->where('final_status', 'rejected')->orWhere('status', 'rejected');
            })->count();
        } catch (\Throwable $e) {
            $total = $pending = $accepted = $rejected = null;
        }

        return [
            'event' => 'LeaveCreated',
            'leave' => [
                'id' => $this->leave->id,
                'user_name' => $this->leave->user?->name ?? null,
                'department' => $this->leave->department ?? null,
                'days' => $this->leave->days ?? null,
                'created_at' => $iso($this->leave->created_at) ?? $iso(now()),
            ],
            'total' => $total,
            'pending' => $pending,
            'accepted' => $accepted,
            'rejected' => $rejected,
        ];
    }

    public function broadcastAs()
    {
        return 'LeaveCreated';
    }
}
