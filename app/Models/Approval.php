<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\Approval
 *
 * @property int $id
 * @property int $leave_request_id
 * @property int $approver_id
 * @property string $action
 * @property string|null $comment
 * @property string|null $stage
 *
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo leaveRequest()
 */
class Approval extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_request_id',
        'approver_id',
        'action',
        'comment',
        'stage',
    ];

    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    protected static function booted()
    {
        static::created(function (self $approval) {
            try {
                event(new \App\Events\ApprovalCreated($approval));
            } catch (\Throwable $e) {
                // ignore broadcasting failures
            }
        });
    }
}
