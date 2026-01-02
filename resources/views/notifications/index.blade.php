@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="max-w-4xl mx-auto p-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            @if (isset($notifications) && $notifications->count())
                <ul class="divide-y">
                    @foreach ($notifications as $n)
                        @php $data = (array) $n->data; @endphp
                        <li class="py-3 flex justify-between items-start gap-4">
                            <div class="flex-1">
                                <div class="font-medium">{{ $data['message'] ?? class_basename($n->type) }}</div>
                                @php
                                    // determine role and badge color
                                    $role =
                                        $data['approver_role'] ??
                                        (isset($data['supervisor'])
                                            ? 'supervisor'
                                            : (isset($data['manager'])
                                                ? 'manager'
                                                : null));
                                    $approverName =
                                        $data['approver'] ?? ($data['supervisor'] ?? ($data['manager'] ?? null));
                                    $roleColors = [
                                        'manager' => 'bg-amber-100 text-amber-800',
                                        'supervisor' => 'bg-emerald-100 text-emerald-800',
                                        'approver' => 'bg-sky-100 text-sky-800',
                                        'hr' => 'bg-violet-100 text-violet-800',
                                    ];
                                    $badgeClass = $role ? $roleColors[$role] ?? 'bg-slate-100 text-slate-800' : null;

                                    // New: determine compact label based on required_approvals payload
                                    $required = $data['required_approvals'] ?? null; // eg ['manager'] or ['hod'] or ['supervisor','manager']
                                    $compactLabel = null;
                                    if (is_array($required) && count($required) === 1) {
                                        if (in_array('hod', $required) || in_array('manager', $required)) {
                                            $compactLabel = 'Approved by Manager/HOD';
                                        } elseif (in_array('manager', $required)) {
                                            $compactLabel = 'Approved by Manager';
                                        } elseif (in_array('supervisor', $required)) {
                                            $compactLabel = 'Approved by Supervisor';
                                        }
                                    }
                                @endphp

                                <div class="text-sm text-slate-500 mt-1 flex flex-col sm:flex-row sm:items-center sm:gap-2">
                                    <div class="flex items-center gap-2">
                                        @if ($compactLabel)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-800">{{ $compactLabel }}</span>
                                        @else
                                            @if ($role)
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">{{ ucfirst($role) }}</span>
                                            @endif
                                            @if ($approverName)
                                                <span class="text-xs text-slate-600">{{ $approverName }}</span>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1 sm:mt-0">
                                        @if (isset($data['employee']))
                                            <span>{{ $data['employee'] }}</span>
                                        @endif
                                        @if (isset($data['leave_type']))
                                            <span class="ml-2">· {{ ucfirst($data['leave_type']) }}</span>
                                        @endif
                                        @if (isset($data['start_date']))
                                            <span class="ml-2">· {{ $data['start_date'] }} @if (isset($data['end_date']))
                                                    — {{ $data['end_date'] }}
                                                @endif
                                            </span>
                                        @endif
                                        <span class="ml-2">· {{ $n->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                @php
                                    $openUrl =
                                        $data['url'] ??
                                        ($data['leave_id'] ? url('/approvals/' . $data['leave_id']) : null);
                                @endphp
                                @if ($openUrl)
                                    <a href="{{ $openUrl }}" class="text-xs text-sky-600 hover:underline">Open</a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4">{{ $notifications->links() }}</div>
            @else
                <p class="text-sm text-gray-600">No notifications yet.</p>
            @endif
        </div>
    </div>
@endsection
