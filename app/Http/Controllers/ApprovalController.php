<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\Approval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\LeaveStatusChanged;
use App\Notifications\LeaveFullyApproved;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ApprovalsExport;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $q = trim((string) $request->get('q', ''));
        $scope = $request->get('scope', 'all'); // 'own' or 'all'

        // For regular employees (non-approver), always show only their own submissions.
        // Prefer an explicit `approver_roles` JSON field on the user record; fall back to role checks.
        $isApprover = false;
        try {
            $rolesJson = $user->approver_roles ?? null;
            $decoded = is_string($rolesJson) ? json_decode($rolesJson, true) : (array) ($rolesJson ?? []);
            $decoded = is_array($decoded) ? $decoded : [];
            $hasApproverRoles = !empty($decoded);

            $isApprover = $hasApproverRoles || (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['manager', 'supervisor', 'administrator', 'admin', 'hr', 'hod']));
        } catch (\Throwable $e) {
            // If parsing fails, fall back to role-based check
            $isApprover = method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['manager', 'supervisor', 'administrator', 'admin', 'hr', 'hod']);
        }

        if (! $isApprover) {
            $scope = 'own';
        }

        // Base query
        $query = LeaveRequest::with('user')->orderBy('created_at', 'desc');

        // Scope: if user asked for own submissions, restrict to their user_id
        if ($scope === 'own') {
            $query->where('user_id', $user->id);
        } else {
            // 'all' scope: for manager/supervisor/hod try to show leaves where
            // the current user is explicitly assigned (primary_supervisor_id or primary_manager_id)
            // and only fall back to department scoping when the approver has a department set.
            if ($user->hasAnyRole(['manager', 'supervisor', 'hod'])) {
                $approverId = $user->id;
                $userDept = $user->department;

                $query->where(function ($qq) use ($approverId, $userDept, $user) {
                    // Leaves where requester explicitly assigned this approver
                    $qq->whereHas('user', function ($u) use ($approverId) {
                        $u->where('primary_supervisor_id', $approverId)
                            ->orWhere('primary_manager_id', $approverId);
                    });

                    // HOD special case: HOD should see all manager (and assistant manager) requests across departments
                    try {
                        if (method_exists($user, 'hasRole') && $user->hasRole('hod')) {
                            $qq->orWhereHas('user', function ($u) {
                                $u->whereHas('roles', function ($r) {
                                    $r->whereIn('name', ['manager', 'assistant manager', 'assistant_manager', 'assistant']);
                                });
                            });
                        }
                    } catch (\Throwable $e) {
                        // ignore role check errors
                    }

                    // Fallback: if approver has a department set, include leaves from that department
                    if (!empty($userDept)) {
                        $qq->orWhere('department', $userDept);
                    }
                });
            }
        }

        // Search q across user name, reason, and leave_type
        if (!empty($q)) {
            $query->where(function ($qq) use ($q) {
                $qq->whereHas('user', function ($u) use ($q) {
                    $u->where('name', 'like', "%{$q}%");
                })->orWhere('reason', 'like', "%{$q}%")
                    ->orWhere('leave_type', 'like', "%{$q}%")
                    ->orWhere('nip', 'like', "%{$q}%");
            });
        }

        // Pending / awaiting approvals
        // Consider a leave pending if it has no final_status or the final_status is explicitly 'pending'.
        // This excludes leaves that have been fully approved (final_status = 'approved'),
        // even if their legacy `status` field remains 'pending'.
        // Before we build the pending/approved queries, apply a supervisor-only
        // exclusion so supervisors don't see manager-created leaves in either list.

        // Prevent supervisors (who are not managers/HODs/admin/hr) from seeing
        // leave requests submitted by users who hold manager roles. This mirrors
        // the same restriction the dashboard applies so supervisors don't see
        // manager-created leaves in Approvals listing either.
        try {
            $isSupervisorOnly = false;
            if (method_exists($user, 'hasAnyRole')) {
                $isSupervisorOnly = $user->hasAnyRole(['supervisor']) && ! $user->hasAnyRole(['manager', 'hod', 'admin', 'hr']);
            } else {
                $roleNames = collect($user->getRoleNames() ?? [])->map(fn($r) => strtolower((string) $r));
                $isSupervisorOnly = $roleNames->contains(fn($r) => str_contains($r, 'supervisor')) && ! $roleNames->contains(fn($r) => str_contains($r, 'manager') || str_contains($r, 'hod') || str_contains($r, 'head') || str_contains($r, 'kepala'));
            }

            if ($isSupervisorOnly) {
                $query->whereHas('user', function ($q) {
                    $q->whereDoesntHave('roles', function ($r) {
                        $r->where('name', 'like', '%manager%');
                    });
                });
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $pendingQuery = clone $query;
        $pendingLeaves = $pendingQuery->where(function ($q) {
            $q->whereNull('final_status')
                ->orWhere('final_status', 'pending');
        })->paginate(12)->appends($request->only(['q', 'scope']));

        // Attach quota and remaining leave info to each leave's user for display
        $quotaPerYear = config('leave.quota', 12);
        $pendingLeaves->getCollection()->transform(function ($leave) use ($quotaPerYear) {
            try {
                $user = $leave->user;
                $used = $user && method_exists($user, 'usedLeaveDaysInCurrentCycle')
                    ? $user->usedLeaveDaysInCurrentCycle()
                    : 0;

                $remaining = max(0, $quotaPerYear - (int) $used);
                if ($user) {
                    $user->setAttribute('quota', $quotaPerYear);
                    $user->setAttribute('remaining_quota', $remaining);
                    $user->setAttribute('used_quota', (int) $used);
                }
            } catch (\Throwable $e) {
                // ignore per-row calculation errors
            }
            return $leave;
        });

        // Already approved
        $approvedQuery = clone $query;
        $approvedLeaves = $approvedQuery->where(function ($q) {
            $q->where('status', 'approved')
                ->orWhere('final_status', 'approved');
        })->paginate(12, ['*'], 'approved_page')->appends($request->only(['q', 'scope']));

        // Attach quota info to approved leaves as well
        $approvedLeaves->getCollection()->transform(function ($leave) use ($quotaPerYear) {
            try {
                $userId = $leave->user_id;
                $used = LeaveRequest::where('user_id', $userId)
                    ->whereYear('start_date', now()->year)
                    ->where(function ($q) {
                        $q->whereIn('final_status', ['approved'])
                            ->orWhereIn('status', ['approved', 'accept', 'accepted']);
                    })->sum('days');

                $remaining = max(0, $quotaPerYear - (int) $used);
                if ($leave->user) {
                    $leave->user->setAttribute('quota', $quotaPerYear);
                    $leave->user->setAttribute('remaining_quota', $remaining);
                    $leave->user->setAttribute('used_quota', (int) $used);
                }
            } catch (\Throwable $e) {
                // ignore
            }
            return $leave;
        });

        return view('approvals.index', compact('pendingLeaves', 'approvedLeaves', 'q', 'scope'));
    }

    public function show(LeaveRequest $leave)
    {
        // show approval history and a form to approve/reject
        // The detailed leave view lives in resources/views/leave/show.blade.php
        // Historically the approvals view included a partial from leave; to ensure
        // the approvals detail page renders even if the partial is missing, render
        // the full `leave.show` view here which contains the detail layout.
        $approvals = Approval::where('leave_request_id', $leave->id)->with('approver')->get();

        // Determine the expected approvers for the employee (prefer explicit primary approvers
        // set on the user record, fallback to role lookup within the same department).
        $expectedSupervisor = null;
        $expectedManager = null;
        $isManagerRequester = false;
        $expectedHod = null;
        try {
            $employee = $leave->user;
            if ($employee) {
                // Prefer explicit primary assignments
                if ($employee->primary_supervisor_id) {
                    $expectedSupervisor = \App\Models\User::find($employee->primary_supervisor_id);
                }
                if ($employee->primary_manager_id) {
                    $expectedManager = \App\Models\User::find($employee->primary_manager_id);
                }

                // If not explicitly assigned, check `approver_roles` JSON on users in same department
                if (! $expectedSupervisor && $employee->department) {
                    $expectedSupervisor = \App\Models\User::where('department', $employee->department)
                        ->whereJsonContains('approver_roles', 'supervisor')
                        ->first();

                    if (! $expectedSupervisor) {
                        // fallback to role helper if available
                        try {
                            $expectedSupervisor = \App\Models\User::role('supervisor')->where('department', $employee->department)->first();
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }
                }

                if (! $expectedManager && $employee->department) {
                    $expectedManager = \App\Models\User::where('department', $employee->department)
                        ->whereJsonContains('approver_roles', 'manager')
                        ->first();

                    if (! $expectedManager) {
                        try {
                            $expectedManager = \App\Models\User::role('manager')->where('department', $employee->department)->first();
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }
                }

                // Determine if requester is a manager (simple check: approver_roles or role helper)
                try {
                    $isManagerRequester = (is_string($employee->approver_roles) && in_array('manager', (array) json_decode($employee->approver_roles, true))) || (method_exists($employee, 'hasAnyRole') && $employee->hasAnyRole(['manager']));
                } catch (\Throwable $e) {
                    $isManagerRequester = false;
                }

                if ($isManagerRequester) {
                    if ($employee->primary_manager_id) {
                        $expectedHod = \App\Models\User::find($employee->primary_manager_id);
                    }

                    if (! $expectedHod && $employee->department) {
                        $expectedHod = \App\Models\User::where('department', $employee->department)
                            ->whereJsonContains('approver_roles', 'hod')
                            ->first();

                        if (! $expectedHod) {
                            try {
                                $expectedHod = \App\Models\User::role('hod')->where('department', $employee->department)->first();
                            } catch (\Throwable $e) {
                                // ignore
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            logger()->warning('Failed to resolve expected approvers for leave.show: ' . $e->getMessage(), ['leave_id' => $leave->id]);
        }

        // Attach quota and remaining leave info to the leave's user so the view
        // can reliably display 'Remaining' even when rendered via this show()
        try {
            $quotaPerYear = config('leave.quota', 12);
            if ($leave->user) {
                $used = method_exists($leave->user, 'usedLeaveDaysInCurrentCycle')
                    ? $leave->user->usedLeaveDaysInCurrentCycle()
                    : 0;

                $remaining = max(0, $quotaPerYear - (int) $used);
                $leave->user->setAttribute('quota', $quotaPerYear);
                $leave->user->setAttribute('remaining_quota', $remaining);
                $leave->user->setAttribute('used_quota', (int) $used);
            }
        } catch (\Throwable $e) {
            // ignore per-row calculation errors
        }

        return view('leave.show', compact('leave', 'approvals', 'expectedSupervisor', 'expectedManager', 'isManagerRequester', 'expectedHod'));
    }

    public function approve(Request $request, LeaveRequest $leave)
    {
        $user = Auth::user();

        // Only authorized approver roles may approve
        if (! $user->hasAnyRole(['administrator', 'admin', 'hr', 'manager', 'supervisor', 'hod'])) {
            abort(403);
        }

        $data = $request->validate([
            'comment' => 'nullable|string|max:2000',
            'stage' => 'nullable|string',
        ]);

        // Guard supervisor approvals before writing audit rows.
        if ($user->hasAnyRole(['supervisor'])) {
            if ($leave->department !== $user->department) {
                abort(403, 'Supervisor can only approve leaves in the same department.');
            }

            $owner = $leave->user;
            $ownerIsApprover = false;
            if ($owner && method_exists($owner, 'hasAnyRole')) {
                $ownerIsApprover = $owner->hasAnyRole(['manager', 'supervisor', 'hod']);
            }

            if ($ownerIsApprover) {
                abort(403, 'Supervisor cannot approve manager/supervisor/HOD leave requests.');
            }
        }

        $approval = Approval::create([
            'leave_request_id' => $leave->id,
            'approver_id' => $user->id,
            'action' => 'approved',
            'comment' => $data['comment'] ?? null,
            'stage' => $data['stage'] ?? null,
        ]);

        // role-specific behavior:
        // - supervisor: record supervisor approval (do NOT finalize)
        // - manager: only allow if supervisor already approved; finalize via manager
        // - admin/hr/administrator: finalize immediately
        try {
            if ($user->hasAnyRole(['supervisor'])) {
                if (!empty($leave->supervisor_approved_at)) {
                    return back()->with('success', 'Supervisor approval already recorded.');
                }

                // Supervisor approves first stage
                $leave->approveBySupervisor($user, $data['comment'] ?? null);
            } elseif ($user->hasAnyRole(['administrator', 'admin', 'hr'])) {
                // admin / hr / administrator - finalize directly
                $leave->update(['status' => 'approved', 'final_status' => 'approved']);

                // Notify the employee that their leave was approved
                if ($leave->user) {
                    $leave->user->notify(new LeaveStatusChanged($leave));
                }

                // Notify HR and Admin about fully approved leave
                $hrAdmins = \App\Models\User::role('hr')->get()->merge(\App\Models\User::role('admin')->get())->unique('id');
                if ($hrAdmins->isNotEmpty()) {
                    Notification::send($hrAdmins, new LeaveFullyApproved($leave));
                }
            } elseif ($user->hasAnyRole(['manager', 'hod'])) {
                // Manager approval: allow manager to approve regardless of supervisor action.
                // The LeaveRequest model will finalize appropriately based on required stages
                // (finalizeAfterApprovals determines if supervisor approval was required for this requester).
                $leave->approveByManager($user, $data['comment'] ?? null);
            }

            // Broadcast approval created so clients viewing the leave can update realtime
            try {
                event(new \App\Events\ApprovalCreated($approval));
            } catch (\Exception $e) {
                logger()->warning('Failed broadcasting approval event: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            logger()->error('Error processing approval: ' . $e->getMessage(), ['leave_id' => $leave->id, 'user_id' => $user->id]);
            return redirect()->route('approvals.show', $leave->id)->with('error', 'Failed to process approval.');
        }

        // Return JSON only for real AJAX/JSON requests.
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'leave_id' => $leave->id,
                'final_status' => $leave->final_status ?? $leave->status,
                'supervisor_approved_at' => $leave->supervisor_approved_at,
                'manager_approved_at' => $leave->manager_approved_at,
            ]);
        }

        return redirect()->route('approvals.show', $leave->id)->with('success', 'Leave approved.');
    }

    /**
     * Export approvals to XLSX. Only available to HR and Admin roles.
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['administrator', 'admin', 'hr'])) {
            abort(403);
        }

        $filters = $request->only(['q', 'scope']);
        $filename = 'approvals_export_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new ApprovalsExport($filters), $filename);
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $user = Auth::user();

        // Only authorized approver roles may reject
        if (! $user->hasAnyRole(['administrator', 'admin', 'hr', 'manager', 'supervisor', 'hod'])) {
            abort(403);
        }

        $data = $request->validate([
            'comment' => 'nullable|string|max:2000',
            'stage' => 'nullable|string',
        ]);

        $approval = Approval::create([
            'leave_request_id' => $leave->id,
            'approver_id' => $user->id,
            'action' => 'rejected',
            'comment' => $data['comment'] ?? null,
            'stage' => $data['stage'] ?? null,
        ]);

        // Broadcast approval created so clients viewing the leave can update realtime
        try {
            event(new \App\Events\ApprovalCreated($approval));
        } catch (\Exception $e) {
            logger()->warning('Failed broadcasting approval event: ' . $e->getMessage());
        }

        $leave->update(['status' => 'rejected', 'final_status' => 'rejected']);

        // Notify the employee that their leave was rejected
        try {
            if ($leave->user) {
                $leave->user->notify(new LeaveStatusChanged($leave));
            }
        } catch (\Exception $e) {
            logger()->warning('Failed sending rejection notification: ' . $e->getMessage());
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'leave_id' => $leave->id,
                'final_status' => $leave->final_status ?? $leave->status,
            ]);
        }

        return redirect()->route('approvals.show', $leave->id)->with('success', 'Leave rejected.');
    }
}
