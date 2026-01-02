<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\Department;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DepartmentApprovalsExport;

class DepartmentApprovalController extends Controller
{
    public function __construct()
    {
        // Supervisors and managers may access department approvals for their department.
        // Also allow HR/Admin roles to access this page so they can view/export by department.
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            // Allow HR/Admin and supervisor/manager roles
            if (! $user || ! $user->hasAnyRole(['administrator', 'admin', 'hr', 'supervisor', 'manager'])) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $departmentFilter = $request->get('department');

        // Only HR/Admin reach this controller (middleware enforces). Provide department filter if present.
        $query = LeaveRequest::query();
        if ($departmentFilter) {
            $query->where('department', $departmentFilter);
        }
        // Show both pending supervisor and pending manager items
        $query->where(function ($q) {
            $q->whereNull('supervisor_approved_at')
                ->orWhere(function ($q2) {
                    $q2->whereNotNull('supervisor_approved_at')->whereNull('manager_approved_at');
                });
        });

        $leaves = $query->latest()->paginate(20);

        // If HR/Admin, provide department list for selector
        $departments = null;
        if ($user->hasAnyRole(['administrator', 'admin', 'hr'])) {
            $departments = Department::orderBy('name')->get();
        }

        return view('department_approval.index', compact('leaves', 'departments'));
    }

    public function show(LeaveRequest $leave)
    {
        $user = auth()->user();
        // ensure same department
        if ($leave->department !== $user->department) {
            abort(403);
        }
        return view('department_approval.show', compact('leave'));
    }

    public function approve(Request $request, LeaveRequest $leave)
    {
        $data = $request->validate([
            'comment' => 'nullable|string',
        ]);

        $user = auth()->user();
        if ($user->hasRole('supervisor')) {
            $leave->approveBySupervisor($user, $data['comment'] ?? null);
        } elseif ($user->hasRole('manager')) {
            // Manager may approve regardless of supervisor approval. The model will
            // finalize if required approvals are present for this requester.
            $leave->approveByManager($user, $data['comment'] ?? null);
        } else {
            abort(403);
        }

        return redirect()->route('department_approval.index')->with('success', 'Action recorded.');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $data = $request->validate([
            'comment' => 'nullable|string',
        ]);

        $user = auth()->user();

        // Allow manager to reject regardless of supervisor action. The rejection
        // sets final_status = 'rejected' and notifies the employee.

        // reject sets final_status = rejected and notifies employee
        $leave->update([
            'final_status' => 'rejected',
            ($user->hasRole('supervisor') ? 'supervisor_id' : 'manager_id') => $user->id,
            ($user->hasRole('supervisor') ? 'supervisor_approved_at' : 'manager_approved_at') => now(),
            ($user->hasRole('supervisor') ? 'supervisor_comment' : 'manager_comment') => $data['comment'] ?? null,
        ]);

        if ($leave->user) {
            $leave->user->notify(new \App\Notifications\LeaveStatusChanged($leave));
        }

        return redirect()->route('department_approval.index')->with('success', 'Leave rejected and employee notified.');
    }

    /**
     * Export department approvals to XLSX for the current user's department.
     * Only available to supervisor and manager (controller middleware already enforces this).
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        if (! $user || ! $user->hasAnyRole(['administrator', 'admin', 'hr'])) {
            abort(403);
        }

        $department = $user->department;
        $filename = 'department_approvals_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', ($department ?? 'dept')) . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new DepartmentApprovalsExport($department), $filename);
    }
}
