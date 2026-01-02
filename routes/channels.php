<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\LeaveRequest;

/**
 * Register channel authorization callbacks.
 */
Broadcast::channel('leave.{leaveId}', function ($user, $leaveId) {
    // allow owner, HR/Admin, or approvers in same department to listen
    $leave = LeaveRequest::find($leaveId);
    if (! $leave) return false;

    if ($user->id === $leave->user_id) return true;
    if ($user->hasAnyRole(['administrator', 'admin', 'hr'])) return true;
    // managers/supervisors in same department can listen
    if ($user->hasAnyRole(['manager', 'supervisor']) && $user->department === $leave->department) return true;

    return false;
});
