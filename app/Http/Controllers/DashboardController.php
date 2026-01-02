<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Return today's leave summary (JSON)
     */
    public function today(Request $request)
    {
        $user = Auth::user();

        // Use same approvals scoping so Today's card matches the Approvals menu
        $query = $this->buildApprovalsQuery($user);

        // Prevent supervisors (who are not managers/HODs/admin/hr) from seeing
        // leave requests submitted by manager users.
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
            // ignore and continue
        }

        // If the current user is a supervisor (but not a manager/hod/admin/hr),
        // do not show leave requests that were submitted by users who are managers.
        // This prevents supervisors from seeing manager-created leave requests.
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
            // swallow and continue - if role checks fail, fall back to previous behavior
        }

        $today = Carbon::today();
        $total = $query->count();
        $todayRequests = (clone $query)->whereDate('start_date', $today->toDateString())->count();
        // Use final_status when present; fall back to legacy status values
        $pending = (clone $query)->where(function ($q) {
            $q->whereNull('final_status')
                ->orWhere('final_status', 'pending');
        })->count();
        $approved = (clone $query)->where(function ($q) {
            $q->whereIn('status', ['approved', 'accept', 'accepted'])
                ->orWhere('final_status', 'approved');
        })->count();
        $rejected = (clone $query)->where(function ($q) {
            $q->whereIn('status', ['rejected', 'denied', 'deny'])
                ->orWhere('final_status', 'rejected');
        })->count();

        // Return a canonical list of users from the database so the All Employees
        // panel always reflects actual employees. Scope to the user's department
        // when appropriate (manager/supervisor) to avoid exposing other departments.
        try {
            $usersQuery = \App\Models\User::query()->orderBy('name');
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['manager', 'supervisor']) && $user->department) {
                $usersQuery->where('department', $user->department);
            }
            $usersCollection = $usersQuery->get();
            $quotaPerYear = config('leave.quota', 12);
            $usersToday = $usersCollection->map(function ($u) use ($quotaPerYear) {
                try {
                    $used = $u->usedLeaveDaysInCurrentCycle();
                    $left = max(0, $quotaPerYear - (int) $used);
                } catch (\Throwable $e) {
                    $left = null;
                }

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'leaves_left' => $left,
                ];
            })->values();
        } catch (\Throwable $e) {
            $usersToday = collect();
        }

        $leaveRequests = (clone $query)
            ->whereDate('start_date', $today->toDateString())
            ->with('user')
            ->get()
            ->map(fn($l) => [
                'id' => $l->id,
                'user_name' => $l->user?->name,
                'type' => $l->type ?? 'leave',
                'start_date' => $this->safeDate($l->start_date),
                'end_date' => $this->safeDate($l->end_date),
                'status' => $l->status,
                'notes' => $l->notes ?? null,
            ]);

        return response()->json([
            'total' => $total,
            'totalRequests' => $total,
            'today' => $todayRequests,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'users' => $usersToday,
            'leaveRequests' => $leaveRequests,
        ]);
    }

    /**
     * Return dashboard stats and a small set of recent leave requests for the current user
     */
    public function stats(Request $request)
    {
        $user = Auth::user();
        // Use same approvals scoping as Approvals menu so dashboard stats
        // for approvers match what they see in the Approvals listing.
        $query = $this->buildApprovalsQuery($user);
        // compute totals for the dashboard cards. Keep scoping (own vs department/global)
        $total = (clone $query)->count();
        $pending = (clone $query)->where(function ($q) {
            $q->whereNull('final_status')
                ->orWhere('final_status', 'pending');
        })->count();
        $accepted = (clone $query)->where(function ($q) {
            $q->whereIn('status', ['approved', 'accept', 'accepted'])
                ->orWhere('final_status', 'approved');
        })->count();
        $rejected = (clone $query)->where(function ($q) {
            $q->whereIn('status', ['rejected', 'denied', 'deny'])
                ->orWhere('final_status', 'rejected');
        })->count();

        // today: leaves that start today
        $today = (clone $query)->whereDate('start_date', Carbon::today()->toDateString())->count();

        // employees: distinct users represented in the query
        // For the dashboard cards we want canonical global counts from the DB so
        // the UI always shows total employees and total departments.
        // This avoids cards showing zero when there are no recent leave records.
        $employees = User::count();
        $departments = Department::count();

        $recent = $query->limit(6)->get()->map(function ($l) {
            return [
                'id' => $l->id,
                'type' => $l->type,
                'start_date' => $this->safeDate($l->start_date),
                'end_date' => $this->safeDate($l->end_date),
                'status' => $l->status,
                'user' => ['id' => $l->user ? $l->user->id : null, 'name' => $l->user ? $l->user->name : null],
                'reason' => \Illuminate\Support\Str::limit($l->reason ?? '', 140),
            ];
        });

        return response()->json([
            'total' => $total,
            // keys expected by the dashboard JS mapping
            'today' => $today,
            'pending' => $pending,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'employees' => $employees,
            'departments' => $departments,
            'recent' => $recent,
        ]);
    }

    /**
     * Render the dashboard view with initial data
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Build a base query scoped similarly to the Approvals controller so
        // the dashboard (for manager/hod) shows the same records as the
        // Approvals menu the approver sees.
        $query = $this->buildApprovalsQuery($user)->with('user')->orderBy('created_at', 'desc');

        // For supervisor-only accounts, exclude requests created by manager users
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

        // Use the full scoped query to compute counts for cards (not limited to 50)
        $totalRequests = (clone $query)->count();
        $todaysRequests = (clone $query)->whereDate('start_date', Carbon::today()->toDateString())->count();
        $pending = (clone $query)->where(function ($q) {
            $q->whereNull('final_status')
                ->orWhere('final_status', 'pending');
        })->count();
        $accepted = (clone $query)->where(function ($q) {
            $q->whereIn('status', ['approved', 'accept', 'accepted'])
                ->orWhere('final_status', 'approved');
        })->count();
        $rejected = (clone $query)->where(function ($q) {
            $q->whereIn('status', ['rejected', 'denied', 'deny'])
                ->orWhere('final_status', 'rejected');
        })->count();
        // Use global counts for the dashboard cards so they show total users and departments
        $employeesCount = User::count();
        $divisionsCount = Department::count();

        // leaveRequests for activity list (limited)
        $leaveRequests = $query->limit(50)->get();

        // All employees for the All Employees panel. Scope by department for managers/supervisors
        try {
            $usersQuery = \App\Models\User::query()->orderBy('name');
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['manager', 'supervisor']) && $user->department) {
                $usersQuery->where('department', $user->department);
            }
            $users = $usersQuery->get();
        } catch (\Throwable $e) {
            $users = collect();
        }

        // Compute simple leave balance (quota per year minus approved days this year)
        $quota = config('leave.quota', 12);
        foreach ($users as $u) {
            $used = method_exists($u, 'usedLeaveDaysInCurrentCycle') ? $u->usedLeaveDaysInCurrentCycle() : 0;
            $left = max(0, $quota - (int) $used);
            $u->setAttribute('leaves_left', $left);
        }

        // basic stats
        // compute summary safely - leaveRequest date fields may sometimes be strings
        $totalRequests = $leaveRequests->count();
        $todaysRequests = $leaveRequests->filter(function ($l) {
            return $this->safeDate($l->start_date) === Carbon::today()->toDateString();
        })->count();
        $pending = $leaveRequests->filter(function ($l) {
            return is_null($l->final_status) || strtolower(($l->final_status ?? $l->status ?? '')) === 'pending';
        })->count();
        $accepted = $leaveRequests->filter(function ($l) {
            $s = strtolower($l->final_status ?? $l->status ?? '');
            return $s === 'approved' || in_array($s, ['accept', 'accepted']);
        })->count();
        $rejected = $leaveRequests->filter(function ($l) {
            $s = strtolower($l->final_status ?? $l->status ?? '');
            return $s === 'rejected' || in_array($s, ['denied', 'deny']);
        })->count();
        // keep the canonical counts (from users/departments) so the cards reflect DB state

        // simple leave quota placeholders - projects may have a formal quota system; default to null
        $quota = null;
        $used = null;
        $remaining = null;

        // If the user is a manager or supervisor, return the manager dashboard (department-scoped)
        // Accept either role names (Spatie) or helper methods (isManager/isSupervisor).
        // Also support case-insensitive substring matches for role names like
        // 'Manager', 'demo-manager', 'senior-supervisor', etc.
        $roleNames = collect();
        try {
            if (method_exists($user, 'getRoleNames')) {
                $roleNames = $user->getRoleNames()->map(fn($r) => strtolower((string) $r));
            }
        } catch (\Throwable $e) {
            $roleNames = collect();
        }

        $isManagerRole = $roleNames->contains(fn($r) => str_contains($r, 'manager'));
        $isSupervisorRole = $roleNames->contains(fn($r) => str_contains($r, 'supervisor'));
        $isHodRole = $roleNames->contains(fn($r) => str_contains($r, 'hod') || str_contains($r, 'head') || str_contains($r, 'kepala'));

        if ($isManagerRole || $isSupervisorRole || $isHodRole || (method_exists($user, 'isManager') && $user->isManager()) || (method_exists($user, 'isSupervisor') && $user->isSupervisor())) {
            $pending = $this->buildApprovalsQuery($user)
                ->where(function ($q) {
                    $q->whereNull('final_status')
                        ->orWhere('final_status', 'pending');
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $history = $this->buildApprovalsQuery($user)
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            // Notifications: guard against missing table
            $notifications = collect();
            try {
                if (Schema::hasTable('notifications')) {
                    $notifications = DB::table('notifications')
                        ->where('notifiable_id', $user->id)
                        ->orderBy('created_at', 'desc')
                        ->limit(20)
                        ->get()
                        ->map(function ($n) {
                            return (object) $n;
                        });
                }
            } catch (\Throwable $e) {
                $notifications = collect();
            }

            return view('manager.dashboard', compact('pending', 'history', 'notifications'));
        }

        return view('dashboard', compact(
            'leaveRequests',
            'totalRequests',
            'todaysRequests',
            'pending',
            'accepted',
            'rejected',
            'employeesCount',
            'divisionsCount',
            'quota',
            'used',
            'remaining',
            'users'
        ));
    }

    /**
     * Safely convert a date-like value to Y-m-d string or null.
     * Accepts Carbon/DateTime, date string or null; returns null on parse errors.
     */
    protected function safeDate($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            // if parse fails, return null and avoid throwing inside JSON endpoints
            logger()->warning('safeDate: failed to parse date value', ['value' => $value, 'exception' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Build a LeaveRequest query scoped the same way the Approvals listing is
     * scoped for the given user. This ensures dashboard cards for HOD/manager
     * reflect the same set of leaves shown in the Approvals menu.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildApprovalsQuery($user)
    {
        $query = \App\Models\LeaveRequest::with('user')->orderBy('created_at', 'desc');

        // Default: non-privileged users only see their own
        try {
            if (! $user->hasAnyRole(['manager', 'supervisor', 'administrator', 'admin', 'hr', 'hod'])) {
                return $query->where('user_id', $user->id);
            }
        } catch (\Throwable $e) {
            // If role checks fail, fall back to own
            return $query->where('user_id', $user->id);
        }

        // For manager/supervisor/hod: show leaves where the approver is assigned
        // (primary_supervisor_id / primary_manager_id), or department-scoped if approver has a department.
        try {
            if ($user->hasAnyRole(['manager', 'supervisor', 'hod'])) {
                $approverId = $user->id;
                $userDept = $user->department;

                $query->where(function ($qq) use ($approverId, $userDept, $user) {
                    $qq->whereHas('user', function ($u) use ($approverId) {
                        $u->where('primary_supervisor_id', $approverId)
                            ->orWhere('primary_manager_id', $approverId);
                    });

                    // HOD special case: include leaves created by manager-role users
                    if (method_exists($user, 'hasRole') && $user->hasRole('hod')) {
                        $qq->orWhereHas('user', function ($u) {
                            $u->whereHas('roles', function ($r) {
                                $r->whereIn('name', ['manager', 'assistant manager', 'assistant_manager', 'assistant']);
                            });
                        });
                    }

                    if (! empty($userDept)) {
                        $qq->orWhere('department', $userDept);
                    }
                });
            }
        } catch (\Throwable $e) {
            // ignore and return whatever $query currently holds
        }

        // Prevent supervisors (who are not managers/HODs/admin/hr) from seeing
        // leave requests submitted by users who hold manager roles.
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
            // ignore and continue
        }

        return $query;
    }
}
