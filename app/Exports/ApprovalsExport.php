<?php

namespace App\Exports;

use App\Models\LeaveRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ApprovalsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $user = auth()->user();

        $q = $this->filters['q'] ?? '';
        $scope = $this->filters['scope'] ?? 'all';
        $departmentFilter = $this->filters['department'] ?? null;

        $query = LeaveRequest::with('user')->orderBy('created_at', 'desc');

        if ($scope === 'own') {
            $query->where('user_id', $user->id);
        } else {
            try {
                if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['manager', 'supervisor'])) {
                    $query->where('department', $user->department);
                }
            } catch (\Throwable $e) {
                // ignore role-check failures and proceed with default scoping
            }
        }

        // If an explicit department filter is provided and the current user is HR/Admin,
        // scope the query to that department. This allows HR/Admin to export department-scoped data.
        if ($departmentFilter && $user->hasAnyRole(['administrator', 'admin', 'hr'])) {
            $query->where('department', $departmentFilter);
        }

        if (! empty($q)) {
            $query->where(function ($qq) use ($q) {
                $qq->whereHas('user', function ($u) use ($q) {
                    $u->where('name', 'like', "%{$q}%");
                })->orWhere('reason', 'like', "%{$q}%")
                    ->orWhere('leave_type', 'like', "%{$q}%")
                    ->orWhere('nip', 'like', "%{$q}%");
            });
        }

        // Prevent supervisors (who are not managers/HODs/admin/hr) from exporting
        // leave requests created by users who hold manager roles so the exported
        // data matches what supervisors see in the UI.
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

        return $query;
    }

    public function map($leave): array
    {
        return [
            $leave->id,
            $leave->user?->name ?? '',
            $leave->user?->email ?? '',
            $leave->nip ?? '',
            $leave->department ?? '',
            $leave->leave_type ?? $leave->type ?? '',
            $leave->start_date ? \Illuminate\Support\Carbon::parse($leave->start_date)->toDateString() : '',
            $leave->end_date ? \Illuminate\Support\Carbon::parse($leave->end_date)->toDateString() : '',
            $leave->days ?? '',
            $leave->supervisor_approved_at ? \Illuminate\Support\Carbon::parse($leave->supervisor_approved_at)->toDateTimeString() : '',
            $leave->manager_approved_at ? \Illuminate\Support\Carbon::parse($leave->manager_approved_at)->toDateTimeString() : '',
            $leave->final_status ?? '',
            $leave->status ?? '',
            str_replace("\n", ' ', $leave->reason ?? ''),
            $leave->created_at ? $leave->created_at->toDateTimeString() : '',
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Employee',
            'Email',
            'NIP',
            'Department',
            'Type',
            'Start Date',
            'End Date',
            'Days',
            'Supervisor Approved At',
            'Manager Approved At',
            'Final Status',
            'Status',
            'Reason',
            'Created At'
        ];
    }
}
