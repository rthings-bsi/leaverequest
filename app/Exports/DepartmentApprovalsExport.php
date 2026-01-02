<?php

namespace App\Exports;

use App\Models\LeaveRequest;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DepartmentApprovalsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $department;

    public function __construct(string $department)
    {
        $this->department = $department;
    }

    public function query()
    {
        $query = LeaveRequest::with('user')
            ->where('department', $this->department)
            ->orderBy('created_at', 'desc');

        // If the current user is a supervisor (but not manager/HOD/admin/hr),
        // exclude leave requests created by manager-role users so exported data
        // matches their UI view.
        try {
            $user = auth()->user();
            if ($user && method_exists($user, 'hasAnyRole')) {
                $isSupervisorOnly = $user->hasAnyRole(['supervisor']) && ! $user->hasAnyRole(['manager', 'hod', 'admin', 'hr']);
            } else {
                $roleNames = collect($user->getRoleNames() ?? [])->map(fn($r) => strtolower((string) $r));
                $isSupervisorOnly = $roleNames->contains(fn($r) => str_contains($r, 'supervisor')) && ! $roleNames->contains(fn($r) => str_contains($r, 'manager') || str_contains($r, 'hod') || str_contains($r, 'head') || str_contains($r, 'kepala'));
            }

            if (! empty($isSupervisorOnly)) {
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
