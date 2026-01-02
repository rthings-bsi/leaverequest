@extends('layouts.app')

@section('title', 'Approvals')

@section('content')
    <div class="max-w-6xl mx-auto p-6">
        <div class="bg-gradient-to-br from-white via-slate-50 to-white rounded-2xl p-6 shadow-sm mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1
                        class="text-3xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-violet-600">
                        Approvals</h1>
                    <div class="text-sm text-gray-500 mt-2">Manage pending leave approvals for your scope</div>
                </div>

                <div class="w-full sm:w-auto flex justify-end">
                    @if (auth()->user()->hasAnyRole(['administrator', 'admin', 'hr']))
                        <form method="GET" action="{{ route('approvals.index') }}">
                            <label class="sr-only">Filter approvals scope</label>
                            <div class="flex items-center">
                                <select name="scope" onchange="this.form.submit()"
                                    class="rounded-full border border-gray-200 bg-white px-5 py-2 text-sm shadow-sm">
                                    <option value="all" {{ isset($scope) && $scope === 'all' ? 'selected' : '' }}>All
                                        employees</option>
                                    <option value="own" {{ isset($scope) && $scope === 'own' ? 'selected' : '' }}>My
                                        submissions</option>
                                </select>
                            </div>
                        </form>
                    @endif
                    {{-- Export button for admin / hr only --}}
                    @if (auth()->user()->hasAnyRole(['administrator', 'admin', 'hr']))
                        @php
                            $qs = http_build_query(request()->only(['q', 'scope']));
                            $exportUrl = route('approvals.export') . ($qs ? '?' . $qs : '');
                        @endphp
                        <a href="{{ $exportUrl }}"
                            class="ml-3 inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-full text-sm shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-200"
                            title="Export approvals to Excel" aria-label="Export approvals to Excel">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Export
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Pending approvals --}}
        @if (isset($pendingLeaves) && $pendingLeaves->count())
            <div class="space-y-6">
                @foreach ($pendingLeaves as $leave)
                    <article
                        class="group relative bg-white rounded-3xl shadow-lg overflow-hidden border border-transparent hover:shadow-2xl transition-transform transform hover:-translate-y-1">
                        <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-indigo-500 to-purple-500"></div>

                        <div class="p-6 lg:p-8 flex flex-col lg:flex-row gap-4 lg:gap-6">
                            <div class="flex-1">
                                <a href="{{ route('approvals.show', $leave->id) }}"
                                    class="text-xl font-semibold text-slate-900 hover:underline">{{ optional($leave->user)->name ?? 'Unknown' }}</a>
                                @if (optional($leave->user)->quota !== null)
                                    <div class="mt-2 text-sm text-slate-500">
                                        Remaining: <span
                                            class="font-medium text-slate-800">{{ optional($leave->user)->remaining_quota ?? 0 }}
                                            days</span>
                                        · Quota: <span
                                            class="font-medium text-slate-800">{{ optional($leave->user)->quota ?? config('leave.quota', 12) }}
                                            days</span>
                                    </div>
                                @endif

                                <div class="mt-2 flex items-center gap-3 flex-wrap text-sm text-gray-600">
                                    <span class="inline-flex items-center gap-2 text-sm text-slate-500"><svg
                                            class="w-3 h-3 text-indigo-500" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3M3 11h18M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>{{ ucfirst($leave->leave_type ?? 'leave') }}</span>
                                    <span class="text-gray-300">•</span>
                                    <span
                                        class="text-sm text-slate-500">{{ $leave->start_date ? \Illuminate\Support\Carbon::parse($leave->start_date)->format('d M Y') : '—' }}
                                        —
                                        {{ $leave->end_date ? \Illuminate\Support\Carbon::parse($leave->end_date)->format('d M Y') : '—' }}</span>
                                    <span class="text-gray-300">•</span>
                                    <span class="text-sm text-slate-500">( {{ $leave->days }} days )</span>
                                    <span class="text-gray-300">•</span>
                                    <span class="text-sm text-slate-500">Submitted:
                                        {{ \Illuminate\Support\Carbon::parse($leave->created_at)->format('d M Y H:i') }}</span>
                                </div>

                                <p class="mt-3 text-sm text-slate-600">
                                    {{ \Illuminate\Support\Str::limit($leave->reason ?? 'No reason provided', 200) }}</p>

                                <div class="mt-4 flex flex-wrap items-center gap-3">
                                    @php
                                        $isOwnerSupervisor =
                                            optional($leave->user) &&
                                            method_exists($leave->user, 'hasRole') &&
                                            $leave->user->hasRole('supervisor');
                                        $isOwnerManager =
                                            optional($leave->user) &&
                                            method_exists($leave->user, 'hasRole') &&
                                            $leave->user->hasRole('manager');
                                    @endphp

                                    @if ($isOwnerManager)
                                        {{-- For leaves owned by managers: show HOD only --}}
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-amber-50 text-amber-700">HOD:
                                            <strong
                                                class="ms-2">{{ $leave->manager_approved_at ? 'Approved' : 'Waiting' }}</strong></span>
                                    @elseif ($isOwnerSupervisor)
                                        {{-- For leaves owned by supervisors: show Manager and HR approval badges only --}}
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-amber-50 text-amber-700">Manager:
                                            <strong
                                                class="ms-2">{{ $leave->manager_approved_at ? 'Approved' : ($leave->supervisor_approved_at ? 'Pending' : 'Waiting') }}</strong></span>

                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-50 text-slate-700">HR:
                                            <strong
                                                class="ms-2">{{ $leave->hr_notified_at ? 'Notified' : ($leave->manager_approved_at ? 'Pending' : 'Waiting') }}</strong></span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-indigo-50 text-indigo-700">Supervisor:
                                            <strong
                                                class="ms-2">{{ $leave->supervisor_approved_at ? 'Approved' : 'Pending' }}</strong></span>
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-amber-50 text-amber-700">Manager:
                                            <strong
                                                class="ms-2">{{ $leave->manager_approved_at ? 'Approved' : ($leave->supervisor_approved_at ? 'Pending' : 'Waiting') }}</strong></span>
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-50 text-slate-700">Final:
                                            <strong
                                                class="ms-2">{{ ucfirst($leave->final_status ?? ($leave->status ?? 'pending')) }}</strong></span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-col items-start lg:items-end justify-between gap-4 w-full lg:w-1/3">
                                <div
                                    class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:justify-end sm:flex-nowrap">
                                    <a href="{{ route('leave.preview', $leave->id) }}"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-rose-50 text-rose-700 rounded-xl shadow-sm hover:shadow-lg text-sm font-semibold ring-1 ring-rose-50 w-full sm:w-auto justify-center sm:justify-start whitespace-nowrap">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-rose-600"
                                            viewBox="0 0 24 24" fill="currentColor">
                                            <path
                                                d="M12 3v10.586l3.293-3.293 1.414 1.414L12 17.414 7.293 12.707l1.414-1.414L12 13.586V3z" />
                                            <path d="M5 20h14v-2H5z" />
                                        </svg>
                                        Preview PDF
                                    </a>

                                    <a href="{{ route('approvals.show', $leave->id) }}"
                                        class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 w-full sm:w-auto justify-center sm:justify-start whitespace-nowrap font-medium">View</a>

                                    @php
                                        $current = auth()->user();
                                        $isSupervisor = $current->hasRole('supervisor');
                                        $isOwner = auth()->id() === $leave->user_id;
                                    @endphp

                                    {{-- If the current user is the owner, allow Edit/Delete even when they also have approver roles --}}
                                    @if ($isOwner)
                                        <a href="{{ route('leave.edit', $leave->id) }}"
                                            class="inline-flex items-center gap-2 px-3 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700 whitespace-nowrap">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                            </svg>
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ route('leave.destroy', $leave->id) }}"
                                            onsubmit="return confirm('Delete this leave request?');" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center gap-2 px-3 py-2 bg-white border rounded-md text-sm text-red-600 hover:bg-red-50 whitespace-nowrap">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" />
                                                </svg>
                                                Delete
                                            </button>
                                        </form>
                                    @elseif ($current->hasAnyRole(['administrator', 'admin', 'hr']))
                                        {{-- Approve / Reject buttons (responsive: full width on phones, inline on sm+) --}}
                                        @if ($isSupervisor && $leave->supervisor_approved_at)
                                            {{-- Supervisor already approved: show disabled approved button --}}
                                            <div class="inline-block ms-0 sm:ms-2 w-full sm:w-auto">
                                                <button type="button" disabled
                                                    class="inline-flex justify-center items-center gap-2 px-4 py-2 bg-gray-300 text-gray-700 rounded-full text-sm w-full sm:w-auto transition whitespace-nowrap cursor-default">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    Approved
                                                </button>
                                                <div class="text-xs text-slate-500 mt-1">
                                                    @php
                                                        // Prefer actual recorded approver; otherwise show expected (from employee master)
                                                        $supName = null;
                                                        if ($leave->supervisor_id) {
                                                            $sup = \App\Models\User::find($leave->supervisor_id);
                                                            $supName = $sup?->name;
                                                        }
                                                        // expected supervisor from employee master
                                                        if (
                                                            !$supName &&
                                                            optional($leave->user)->primary_supervisor_id
                                                        ) {
                                                            $ps = \App\Models\User::find(
                                                                $leave->user->primary_supervisor_id,
                                                            );
                                                            $supName = $ps?->name;
                                                        }
                                                        // fallback to role+department lookup
                                                        if (!$supName && optional($leave->user)->department) {
                                                            $ps = \App\Models\User::role('supervisor')
                                                                ->where('department', $leave->user->department)
                                                                ->first();
                                                            $supName = $ps?->name;
                                                        }
                                                    @endphp
                                                    @if ($supName)
                                                        Approved by {{ $supName }} at <time class="local-time"
                                                            data-field="supervisor_approved_at"
                                                            datetime="{{ \Illuminate\Support\Carbon::parse($leave->supervisor_approved_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($leave->supervisor_approved_at)->toDateTimeString() }}</time>
                                                    @else
                                                        Approved at <time class="local-time"
                                                            data-field="supervisor_approved_at"
                                                            datetime="{{ \Illuminate\Support\Carbon::parse($leave->supervisor_approved_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($leave->supervisor_approved_at)->toDateTimeString() }}</time>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <form method="POST" action="{{ route('approvals.approve', $leave->id) }}"
                                                class="inline-block ms-0 sm:ms-2 w-full sm:w-auto"
                                                onsubmit="return confirm('Approve this leave?');">
                                                @csrf
                                                <input type="hidden" name="ajax" value="1">
                                                <input type="hidden" name="comment" value="Approved via approvals list">
                                                <button type="submit"
                                                    class="inline-flex justify-center items-center gap-2 px-4 py-2 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-full text-sm w-full sm:w-auto hover:from-emerald-600 hover:to-emerald-700 transition whitespace-nowrap">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    Approve
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('approvals.reject', $leave->id) }}"
                                            class="inline-block ms-0 sm:ms-2 w-full sm:w-auto"
                                            onsubmit="return confirm('Reject this leave?');">
                                            @csrf
                                            <input type="hidden" name="ajax" value="1">
                                            <input type="hidden" name="comment" value="Rejected via approvals list">
                                            <button type="submit"
                                                class="inline-flex justify-center items-center gap-2 px-3 py-2 bg-white border border-red-100 rounded-full text-sm text-red-600 w-full sm:w-auto hover:bg-red-50 transition whitespace-nowrap">
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                Reject
                                            </button>
                                        </form>
                                    @else
                                        {{-- For regular employees, offer Edit and Delete on their own submission only --}}
                                        @if (auth()->id() === $leave->user_id)
                                            <a href="{{ route('leave.edit', $leave->id) }}"
                                                class="inline-flex items-center gap-2 px-3 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700 whitespace-nowrap">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                                </svg>
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('leave.destroy', $leave->id) }}"
                                                onsubmit="return confirm('Delete this leave request?');"
                                                class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex items-center gap-2 px-3 py-2 bg-white border rounded-md text-sm text-red-600 hover:bg-red-50 whitespace-nowrap">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" />
                                                    </svg>
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>

                                <div class="text-sm text-gray-500 text-right w-full">
                                    {{-- show final_status when present, otherwise fallback to status --}}
                                    <div class="font-medium text-slate-800">
                                        {{ ucfirst($leave->final_status ?? ($leave->status ?? 'pending')) }}
                                    </div>
                                    <div class="mt-1 text-xs">{{ $leave->admin_comment ?? '' }}</div>
                                </div>
                                <div class="mt-2 text-xs text-slate-400">
                                    @if ($leave->created_at)
                                        Created: <time class="local-time" data-field="created_at"
                                            datetime="{{ \Illuminate\Support\Carbon::parse($leave->created_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($leave->created_at)->toDateTimeString() }}</time>
                                    @endif
                                    @if ($leave->updated_at)
                                        · Updated: <time class="local-time" data-field="updated_at"
                                            datetime="{{ \Illuminate\Support\Carbon::parse($leave->updated_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($leave->updated_at)->toDateTimeString() }}</time>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach

                <div class="pt-4">{{ $pendingLeaves->links() }}</div>
            </div>
        @else
            <div class="p-6 bg-white border rounded text-gray-600">No leave requests pending for approval.</div>
        @endif

        {{-- Approved approvals --}}
        <div class="mt-8">
            <h2 class="text-2xl font-semibold mb-4">Approved leave requests</h2>

            @if (isset($approvedLeaves) && $approvedLeaves->count())
                <div class="space-y-6">
                    @foreach ($approvedLeaves as $leave)
                        <article
                            class="group relative bg-white rounded-3xl shadow-lg overflow-hidden border border-transparent hover:shadow-2xl transition-transform transform hover:-translate-y-1">
                            <div class="absolute inset-y-0 left-0 w-1 bg-emerald-500"></div>

                            <div class="p-6 lg:p-8 flex flex-col lg:flex-row gap-4 lg:gap-6">
                                <div class="flex-1">
                                    <a href="{{ route('approvals.show', $leave->id) }}"
                                        class="text-xl font-semibold text-slate-900 hover:underline">{{ optional($leave->user)->name ?? 'Unknown' }}</a>
                                    @if (optional($leave->user)->quota !== null)
                                        <div class="mt-2 text-sm text-slate-500">
                                            Remaining: <span
                                                class="font-medium text-slate-800">{{ optional($leave->user)->remaining_quota ?? 0 }}
                                    @endif
                                    <strong
                                        class="ms-2">{{ ucfirst($leave->final_status ?? ($leave->status ?? 'approved')) }}</strong></span>
                                </div>
                            </div>

                            <div class="flex flex-col items-start lg:items-end justify-between gap-4 w-full lg:w-1/3">
                                <div
                                    class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:justify-end">
                                    <a href="{{ route('leave.preview', $leave->id) }}"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-rose-50 text-rose-700 rounded-lg shadow-sm hover:shadow-md text-sm font-medium w-full sm:w-auto justify-center sm:justify-start">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-rose-600"
                                            viewBox="0 0 24 24" fill="currentColor">
                                            <path
                                                d="M12 3v10.586l3.293-3.293 1.414 1.414L12 17.414 7.293 12.707l1.414-1.414L12 13.586V3z" />
                                            <path d="M5 20h14v-2H5z" />
                                        </svg>
                                        Preview PDF
                                    </a>

                                    <a href="{{ route('approvals.show', $leave->id) }}"
                                        class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-gray-100 rounded-lg text-sm text-gray-700 w-full sm:w-auto justify-center sm:justify-start">View</a>
                                </div>
                                @php
                                    $isOwner = auth()->id() === $leave->user_id;
                                    $isApproved =
                                        strtolower($leave->final_status ?? ($leave->status ?? 'pending')) ===
                                        'approved';
                                @endphp
                                @if ($isOwner && !$isApproved)
                                    <div class="mt-2 sm:mt-0">
                                        <a href="{{ route('leave.edit', $leave->id) }}"
                                            class="inline-flex items-center gap-2 px-3 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700 whitespace-nowrap">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                            </svg>
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ route('leave.destroy', $leave->id) }}"
                                            onsubmit="return confirm('Delete this leave request?');"
                                            class="inline-block ms-2">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center gap-2 px-3 py-2 bg-white border rounded-md text-sm text-red-600 hover:bg-red-50 whitespace-nowrap">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" />
                                                </svg>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                @endif

                                <div class="text-sm text-gray-500 text-right w-full">
                                    <div class="font-medium text-slate-800">
                                        {{ ucfirst($leave->final_status ?? ($leave->status ?? 'approved')) }}
                                    </div>
                                    <div class="mt-1 text-xs">{{ $leave->admin_comment ?? '' }}</div>
                                </div>
                                <div class="mt-2 text-xs text-slate-400">
                                    @if ($leave->created_at)
                                        Submitted: <time class="local-time"
                                            datetime="{{ \Illuminate\Support\Carbon::parse($leave->created_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($leave->created_at)->toDateTimeString() }}</time>
                                    @endif
                                    @if ($leave->updated_at)
                                        · Updated: <time class="local-time"
                                            datetime="{{ \Illuminate\Support\Carbon::parse($leave->updated_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($leave->updated_at)->toDateTimeString() }}</time>
                                    @endif
                                </div>
                            </div>
                </div>
                </article>
            @endforeach

            <div class="pt-4">{{ $approvedLeaves->links('pagination::tailwind') }}</div>
        </div>
    @else
        <div class="p-6 bg-white border rounded text-gray-600">No approved leave requests found.</div>
        @endif
    </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Ensure a global renderLocalTimes exists (safe to redefine if present)
        (function() {
            window.serverOffset = window.serverOffset || 0;

            window.renderLocalTimes = window.renderLocalTimes || function() {
                const nodes = document.querySelectorAll('time.local-time');
                nodes.forEach(function(node) {
                    const iso = node.getAttribute('datetime');
                    if (!iso) return;
                    const d = new Date(iso);
                    if (isNaN(d.getTime())) return;
                    // Apply server offset if available
                    const now = new Date(Date.now() + (window.serverOffset || 0));

                    const df = new Intl.DateTimeFormat(undefined, {
                        year: 'numeric',
                        month: 'short',
                        day: '2-digit'
                    });
                    const tf = new Intl.DateTimeFormat(undefined, {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                    node.textContent = df.format(d) + ' ' + tf.format(d);
                    node.title = d.toString();
                });
            };

            async function resyncServerTime() {
                try {
                    const res = await fetch('{{ route('server.time') }}', {
                        credentials: 'same-origin'
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (data && data.now) {
                        const serverDate = new Date(data.now);
                        const clientDate = new Date();
                        if (!isNaN(serverDate.getTime())) {
                            window.serverOffset = serverDate.getTime() - clientDate.getTime();
                        }
                    }
                } catch (e) {
                    // ignore
                }
            }

            // initial render and sync
            try {
                window.renderLocalTimes();
            } catch (e) {}
            resyncServerTime();

            // update local times every second for realtime seconds display
            setInterval(function() {
                try {
                    window.renderLocalTimes();
                } catch (e) {}
            }, 1000);

            // resync every 5 minutes to avoid long drift
            setInterval(resyncServerTime, 5 * 60 * 1000);
        })();
    </script>
    <script>
        // AJAX approve/reject handlers: hijack form submit, call API, update DOM
        (function() {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            function postForm(form) {
                const url = form.getAttribute('action');
                const formData = new FormData(form);
                return fetch(url, {
                    method: form.getAttribute('method') || 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: formData,
                    credentials: 'same-origin'
                }).then(r => r.json());
            }

            function disableApproveButton(btn) {
                if (!btn) return;
                btn.disabled = true;
                btn.classList.remove('from-emerald-500', 'to-emerald-600', 'hover:from-emerald-600',
                    'hover:to-emerald-700');
                btn.classList.add('bg-gray-300', 'text-gray-700', 'cursor-default');
                btn.textContent = 'Approved';
            }

            function moveToApproved(article) {
                if (!article) return;
                // change left border to emerald
                const left = article.querySelector('.absolute.inset-y-0.left-0.w-1');
                if (left) left.className = 'absolute inset-y-0 left-0 w-1 bg-emerald-500';

                // remove approve/reject forms
                const forms = article.querySelectorAll('form');
                forms.forEach(f => f.remove());

                // find approved container
                let approvedSection = document.querySelector('div.mt-8');
                if (approvedSection) {
                    let list = approvedSection.querySelector('.space-y-6');
                    if (!list) {
                        // create list container
                        list = document.createElement('div');
                        list.className = 'space-y-6';
                        // insert after the heading
                        approvedSection.appendChild(list);
                    }
                    // append the article to approved list
                    list.prepend(article);
                }
            }

            document.addEventListener('submit', function(e) {
                const form = e.target.closest('form');
                if (!form) return;
                const action = form.getAttribute('action') || '';
                if (!/approvals\/.+\/(approve|reject)$/.test(action)) return; // not our form

                e.preventDefault();
                // confirm already handled inline; proceed with AJAX
                postForm(form).then(data => {
                    if (!data || !data.success) {
                        // fallback to full submit if something went wrong
                        form.submit();
                        return;
                    }

                    const article = form.closest('article');
                    if (!article) return;

                    if (action.endsWith('/approve')) {
                        // disable approve button and move to approved
                        const btn = article.querySelector('button[type="submit"]');
                        disableApproveButton(btn);
                        // update status badges
                        const managerBadge = article.querySelectorAll('.inline-flex')[1];
                        if (managerBadge) {
                            const strong = managerBadge.querySelector('strong');
                            if (strong) strong.textContent = 'Approved';
                        }
                        const finalBadge = article.querySelectorAll('.inline-flex')[2];
                        if (finalBadge) {
                            const strong = finalBadge.querySelector('strong');
                            if (strong) strong.textContent = (data.final_status || 'approved');
                        }

                        moveToApproved(article);
                    } else if (action.endsWith('/reject')) {
                        // remove article or mark rejected
                        const finalBadge = article.querySelectorAll('.inline-flex')[2];
                        if (finalBadge) {
                            const strong = finalBadge.querySelector('strong');
                            if (strong) strong.textContent = (data.final_status || 'rejected');
                        }
                        // remove action buttons
                        const btns = article.querySelectorAll('form, a.inline-flex');
                        btns.forEach(n => n.remove());
                        // optionally move to approved section? we'll leave it in place but visually updated
                    }
                }).catch(() => {
                    // network error: fallback to default submit
                    form.submit();
                });
            }, true);
        })();
    </script>
@endpush
