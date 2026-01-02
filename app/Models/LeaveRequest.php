<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Notifications\LeaveStatusChanged;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * App\Models\LeaveRequest
 *
 * @property int $id
 * @property int $user_id
 * @property-read \App\Models\User|null $user
 * @property int|null $approver_id
 * @property int|null $supervisor_id
 * @property int|null $manager_id
 * @property string|null $status
 * @property string|null $final_status
 * @property string|null $department
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 *
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo user()
 */
class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type',
        'start_date',
        'end_date',
        'days',
        'reason',
        'status',
        'approver_id',
        'approved_at',
        'admin_comment',
        // extra fields
        'nip',
        'department',
        'mandatory_document',
        'period',
        'cover_by',
        'attachment_path',
        // multi-stage approval
        'supervisor_id',
        'supervisor_approved_at',
        'supervisor_comment',
        'manager_id',
        'manager_approved_at',
        'manager_comment',
        'final_status',
        'hr_notified_at',
    ];

    protected $dates = ['start_date', 'end_date', 'approved_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * The manager who approved/owns this leave (if any).
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Normalize a notifiables list into a deduplicated collection of User models.
     * Accepts arrays, collections, single models, or mixed values.
     */
    protected static function dedupeNotifiables($items)
    {
        $col = collect($items);
        // flatten one level to handle nested arrays/collections
        $flat = $col->flatten(1)->filter();
        return $flat->unique('id')->values();
    }

    protected static function booted()
    {
        // When a leave request is created (from any entrypoint: controller, Filament, scripts)
        // notify HR/Admin and department approvers so they see the new leave in their notifications menu.
        static::created(function (self $leave) {
            try {
                // HR/Admin receive a global notification. Accept multiple role name variants for safety.
                $hrRoles = ['hr', 'human-resources', 'administrator', 'admin'];
                $hrAdmins = collect();
                foreach ($hrRoles as $r) {
                    try {
                        $hrAdmins = $hrAdmins->merge(User::role($r)->get());
                    } catch (\Throwable $ignored) {
                        // role may not exist; ignore
                    }
                }
                $hrAdmins = $hrAdmins->unique('id');
                if ($hrAdmins->isNotEmpty()) {
                    // Ensure recipients are unique (a user may have multiple matching roles)
                    $recipients = self::dedupeNotifiables($hrAdmins);
                    // Use sendNow so database notifications are created immediately when no queue worker is running
                    NotificationFacade::sendNow($recipients, new \App\Notifications\NewLeaveSubmitted($leave));
                }

                // Department-level approvers: decide which roles to notify based on requester role
                if (! empty($leave->department)) {
                    // Determine required approver roles similar to finalizeAfterApprovals
                    $approverRoles = ['supervisor', 'manager'];
                    try {
                        $requester = $leave->user ?? null;
                        if ($requester && method_exists($requester, 'hasAnyRole')) {
                            try {
                                if ($requester->hasAnyRole(['manager'])) {
                                    $approverRoles = ['hod'];
                                } elseif ($requester->hasAnyRole(['supervisor'])) {
                                    $approverRoles = ['manager'];
                                }
                            } catch (\Throwable $e) {
                                // ignore
                            }
                        }
                    } catch (\Exception $e) {
                        // default remains
                    }

                    // fetch users for those roles in the department
                    try {
                        $deptApprovers = collect();
                        foreach ($approverRoles as $r) {
                            try {
                                // If the approver role is HOD and the requester is a manager, notify HODs across all departments
                                // so HOD accounts will receive manager-submitted leaves even when departments don't match.
                                if (strtolower($r) === 'hod') {
                                    $deptApprovers = $deptApprovers->merge(User::role($r)->get());
                                } else {
                                    $deptApprovers = $deptApprovers->merge(User::role($r)->where('department', $leave->department)->get());
                                }
                            } catch (\Throwable $e) {
                                // role may not exist, fallback to users by department for this role
                                if (strtolower($r) === 'hod') {
                                    $deptApprovers = $deptApprovers->merge(User::get());
                                } else {
                                    $deptApprovers = $deptApprovers->merge(User::where('department', $leave->department)->get());
                                }
                            }
                        }
                        $deptApprovers = $deptApprovers->unique('id');
                    } catch (\Throwable $e) {
                        $deptApprovers = User::where('department', $leave->department)->get();
                    }

                    if ($deptApprovers->isNotEmpty()) {
                        // Deduplicate by id in case role queries returned overlapping users
                        $deptRecipients = self::dedupeNotifiables($deptApprovers);
                        // Send synchronously and include required_approvals info in the notification payload
                        NotificationFacade::sendNow($deptRecipients, new \App\Notifications\NewLeaveForApprover($leave));
                    }
                }
            } catch (\Exception $e) {
                logger()->error('Failed to send created leave notifications: ' . $e->getMessage());
            }
            // Broadcast a light-weight event so dashboards/list pages can refresh in realtime
            try {
                event(new \App\Events\LeaveCreated($leave));
            } catch (\Throwable $e) {
                // non-fatal if broadcasting isn't configured
            }
        });

        // When final_status is changed to 'approved' by any path (scripts, direct update, or approval helpers),
        // ensure HR/Admin and the employee receive their notifications. This handles cases where the
        // model was updated outside of the helper methods.
        static::updated(function (self $leave) {
            try {
                if ($leave->wasChanged('final_status') && $leave->final_status === 'approved') {
                    // Notify HR/Admin if not already notified
                    if (empty($leave->hr_notified_at)) {
                        try {
                            // role lookup can throw if a role name doesn't exist; guard with try-catch
                            try {
                                $recipients = \App\Models\User::role(['hr', 'administrator', 'admin'])->get();
                            } catch (\Throwable $e) {
                                $recipients = collect();
                                try {
                                    $recipients = \App\Models\User::role('hr')->get();
                                } catch (\Throwable $e2) {
                                    // ignore, no hr role
                                }
                            }

                            if ($recipients->isNotEmpty()) {
                                $recipients = self::dedupeNotifiables($recipients);
                                \Illuminate\Support\Facades\Notification::sendNow($recipients, new \App\Notifications\LeaveFullyApproved($leave));
                                $leave->hr_notified_at = now();
                                $leave->save();
                            }
                        } catch (\Exception $e) {
                            logger()->error('Failed to send HR/Admin notification on final_status update: ' . $e->getMessage());
                        }
                    }

                    // Notify employee if they don't already have a LeaveApprovedEmployee notification for this leave
                    try {
                        if ($leave->user) {
                            $exists = $leave->user->notifications()->where('type', \App\Notifications\LeaveApprovedEmployee::class)->where('data->leave_id', $leave->id)->exists();
                            if (! $exists) {
                                \Illuminate\Support\Facades\Notification::sendNow([$leave->user], new \App\Notifications\LeaveApprovedEmployee($leave));
                            }
                        }
                    } catch (\Exception $e) {
                        logger()->warning('Failed to notify employee on final_status update: ' . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                // swallow to avoid blocking the update
                logger()->warning('final_status updated handler failed: ' . $e->getMessage());
            }
        });

        // Broadcast light-weight updates when relevant timestamp fields change so
        // clients can update created_at/approved timestamps in realtime.
        static::updated(function (self $leave) {
            try {
                // Broadcast only when time-related fields changed to avoid noisy updates
                $timeFields = ['created_at', 'updated_at', 'supervisor_approved_at', 'manager_approved_at', 'hr_notified_at', 'final_status'];
                $changed = array_intersect($leave->getChanges() ? array_keys($leave->getChanges()) : [], $timeFields);
                if (! empty($changed)) {
                    event(new \App\Events\LeaveUpdated($leave));
                }
            } catch (\Throwable $e) {
                // non-fatal
            }
        });
    }

    public function approve(User $approver, $comment = null)
    {
        // legacy single-step approval (kept for compatibility)
        $this->update([
            'status' => 'approved',
            'approver_id' => $approver->id,
            'approved_at' => now(),
            'admin_comment' => $comment,
            'final_status' => 'approved',
        ]);

        if ($this->user) {
            $this->user->notify(new LeaveStatusChanged($this));
        }
    }

    public function reject(User $approver, $comment = null)
    {
        // legacy single-step reject
        $this->update([
            'status' => 'rejected',
            'approver_id' => $approver->id,
            'approved_at' => now(),
            'admin_comment' => $comment,
            'final_status' => 'rejected',
        ]);

        if ($this->user) {
            $this->user->notify(new LeaveStatusChanged($this));
        }
    }

    /**
     * Approve by supervisor (first stage).
     */
    public function approveBySupervisor(User $supervisor, $comment = null)
    {
        $this->update([
            'supervisor_id' => $supervisor->id,
            'supervisor_approved_at' => now(),
            'supervisor_comment' => $comment,
        ]);

        // Notify managers in the same department
        try {
            $managers = \App\Models\User::role('manager')->where('department', $this->department)->get();
            if ($managers->isNotEmpty()) {
                // Deduplicate manager list just in case
                $managersRecipients = self::dedupeNotifiables($managers);
                // Use sendNow to ensure the notification is persisted immediately
                \Illuminate\Support\Facades\Notification::sendNow($managersRecipients, new \App\Notifications\SupervisorApprovedForManager($this, $supervisor->name ?? null));
            }
        } catch (\Exception $e) {
            logger()->error('Failed to notify managers: ' . $e->getMessage());
        }

        // Notify the employee that their leave was approved by supervisor
        try {
            if ($this->user) {
                \Illuminate\Support\Facades\Notification::sendNow([$this->user], new \App\Notifications\SupervisorApproved($this));
            }
        } catch (\Exception $e) {
            logger()->warning('Failed to notify employee about supervisor approval: ' . $e->getMessage());
        }

        // If manager has already approved as well, finalize and notify HR/admin/employee
        try {
            $this->refresh();
            $this->finalizeAfterApprovals();
        } catch (\Exception $e) {
            // don't fail the supervisor approval if finalization has issues
            logger()->warning('Finalize after supervisor approval failed: ' . $e->getMessage());
        }
    }

    /**
     * Approve by manager (second stage) and finalize.
     */
    public function approveByManager(User $manager, $comment = null)
    {
        $this->update([
            'manager_id' => $manager->id,
            'manager_approved_at' => now(),
            'manager_comment' => $comment,
            // don't finalize here unless supervisor approval is present; finalization will set status/final_status
        ]);

        // Notify employee and HR/Admin that manager has approved (intermediate notification)
        try {
            // send manager-approved notification to employee
            if ($this->user) {
                \Illuminate\Support\Facades\Notification::sendNow([$this->user], new \App\Notifications\ManagerApproved($this, $manager->name ?? null));
            }

            // notify HR/Admin about manager approval as an update
            try {
                $recipients = \App\Models\User::role(['hr', 'administrator', 'admin'])->get();
            } catch (\Throwable $e) {
                try {
                    $recipients = \App\Models\User::role('hr')->get();
                } catch (\Throwable $e2) {
                    $recipients = collect();
                }
            }

            if (!empty($recipients) && $recipients->isNotEmpty()) {
                $recipients = self::dedupeNotifiables($recipients);
                \Illuminate\Support\Facades\Notification::sendNow($recipients, new \App\Notifications\ManagerApproved($this, $manager->name ?? null));
            }
        } catch (\Exception $e) {
            logger()->warning('Failed to send manager-approved notifications: ' . $e->getMessage());
        }

        // Try to finalize if supervisor approval already exists; otherwise finalization will occur when supervisor approves.
        try {
            $this->refresh();
            $this->finalizeAfterApprovals();
        } catch (\Exception $e) {
            logger()->warning('Finalize after manager approval failed: ' . $e->getMessage());
        }
    }

    /**
     * If both supervisor and manager approvals exist, mark the leave as fully approved
     * and notify HR/Admin and the employee. This method is idempotent and will
     * not duplicate notifications if final_status is already 'approved'.
     */
    protected function finalizeAfterApprovals()
    {
        // Determine required approval stages based on requester's role.
        // - If requester is a manager => only manager approval (by HOD) is required.
        // - If requester is a supervisor => only manager approval is required.
        // - Otherwise (regular employee) => both supervisor and manager approvals are required.
        $needsSupervisor = true;
        $needsManager = true;

        try {
            $requester = $this->user ?? null;
            $isManagerRequester = false;
            $isSupervisorRequester = false;
            if ($requester) {
                if (method_exists($requester, 'hasAnyRole')) {
                    try {
                        $isManagerRequester = $requester->hasAnyRole(['manager']);
                        $isSupervisorRequester = $requester->hasAnyRole(['supervisor']);
                    } catch (\Throwable $e) {
                        // ignore role check errors
                    }
                }
            }

            if ($isManagerRequester) {
                // manager requested leave: only manager/HOD approval required
                $needsSupervisor = false;
                $needsManager = true;
            } elseif ($isSupervisorRequester) {
                // supervisor requested leave: only manager approval required
                $needsSupervisor = false;
                $needsManager = true;
            } else {
                // regular employee: keep two-stage approval
                $needsSupervisor = true;
                $needsManager = true;
            }
        } catch (\Exception $e) {
            // default to two-stage approval on error
            $needsSupervisor = true;
            $needsManager = true;
        }

        // Ensure required approvals present
        if ($needsSupervisor && empty($this->supervisor_approved_at)) {
            return;
        }
        if ($needsManager && empty($this->manager_approved_at)) {
            return;
        }

        // Already finalized? noop
        if ($this->final_status === 'approved') {
            return;
        }

        // update final status and legacy status
        $this->update([
            'status' => 'approved',
            'final_status' => 'approved',
        ]);

        // Notify HR/Admin
        try {
            $recipients = \App\Models\User::role(['hr', 'administrator', 'admin'])->get();
            if ($recipients->isNotEmpty()) {
                $recipients = self::dedupeNotifiables($recipients);
                \Illuminate\Support\Facades\Notification::sendNow($recipients, new \App\Notifications\LeaveFullyApproved($this));
                $this->hr_notified_at = now();
                $this->save();
            }
        } catch (\Exception $e) {
            logger()->error('Failed to notify HR/Admin on finalization: ' . $e->getMessage());
        }

        // Notify employee (sendNow to persist DB notification immediately even if the Notification implements ShouldQueue)
        try {
            if ($this->user) {
                \Illuminate\Support\Facades\Notification::sendNow([$this->user], new \App\Notifications\LeaveApprovedEmployee($this));
            }
        } catch (\Exception $e) {
            logger()->warning('Failed to notify employee about final approval: ' . $e->getMessage());
            try {
                if ($this->user) {
                    \Illuminate\Support\Facades\Notification::sendNow([$this->user], new \App\Notifications\LeaveStatusChanged($this));
                }
            } catch (\Exception $e) {
                logger()->error('Fallback notify failed: ' . $e->getMessage());
            }
        }
    }
}
