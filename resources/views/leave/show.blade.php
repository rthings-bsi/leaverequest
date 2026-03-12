@extends('layouts.app')

@section('title', 'Leave Details')

@section('content')
    {{-- Expose leave id for private Echo subscription --}}
    <script>
        window.__LEAVE_ID__ = @json($leave->id ?? null);
    </script>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- Top dashboard/banner removed on leave pages per user request -->

        @unless (request()->routeIs('leave.*') || request()->routeIs('approvals.*'))
            <!-- Header / Hero: left quota card + right request summary (hidden on leave/approvals pages) -->
            <div class="rounded-2xl p-6 shadow-xl border border-gray-150"
                style="background: linear-gradient(200deg, #83A4D4 40%, #B6FBFF 100%);">
                <div class="max-w-4xl mx-auto">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                        <!-- Left: quota panel -->
                        <div class="lg:col-span-1">
                            <div class="bg-blue-50 rounded-xl p-6 h-full flex flex-col justify-between lg:min-h-[160px]">
                                <div>
                                    <div class="text-sm text-slate-700">Your remaining leave quota</div>
                                    <div class="mt-3 flex items-baseline gap-3">
                                        <div id="user-remaining-quota" class="text-5xl font-extrabold text-slate-900">
                                            {{ $leave->user?->remaining_quota ?? 0 }}</div>
                                        <div class="text-lg font-medium text-slate-700">days</div>
                                    </div>
                                    <div class="mt-2 text-sm text-slate-600">Quota: <span
                                            id="user-quota">{{ $leave->user?->quota ?? 0 }}</span> days</div>
                                </div>

                                <div class="mt-4 lg:mt-0">
                                    <div class="bg-white rounded-lg p-4 shadow-sm w-40">
                                        <div class="text-sm">🌱 Go Green</div>
                                        <div class="mt-2 text-sm">Saved 1 sheets<br /><span class="text-xs text-slate-500">~0.01
                                                small trees</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: leave request summary card (left-aligned to avoid over-centering) -->
                        <div class="lg:col-span-2">
                            <div class="bg-white rounded-xl p-6 shadow-sm w-full lg:w-2/3 lg:min-h-[160px]">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm text-slate-500">Your leave request</div>
                                        <div class="mt-2 font-semibold text-lg text-slate-900">
                                            {{ ucfirst($leave->leave_type ?? '—') }}
                                            <div class="text-sm text-slate-500">to
                                                {{ $leave->end_date ? \Illuminate\Support\Carbon::parse($leave->end_date)->format('d M Y') : '—' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        @php
                                            $st = strtolower(
                                                trim($leave->final_status ?? ($leave->status ?? 'pending')),
                                            );
                                        @endphp
                                        <span id="status-badge-top"
                                            class="px-4 py-2 rounded-full text-sm {{ $st === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($st === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700') }}">
                                            {{ ucfirst($st) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endunless

        <!-- Main content -->
        <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4">
                <!-- Details card -->
                <div class="relative bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div
                        class="absolute left-0 top-0 h-full w-1 rounded-l-2xl bg-gradient-to-b from-indigo-200 to-indigo-100">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-slate-400">Employee</div>
                            <div class="font-semibold text-lg text-slate-800">{{ $leave->user?->name ?? '—' }}</div>

                            <div class="mt-4 text-sm text-slate-400">NIP</div>
                            <div class="font-medium text-slate-800">{{ $leave->nip ?? '—' }}</div>

                            <div class="mt-4 text-sm text-slate-400">Department</div>
                            <div class="font-medium text-slate-800">{{ $leave->department ?? '—' }}</div>

                            <div class="mt-4 text-sm text-slate-400">Remaining</div>
                            <div class="font-medium text-slate-800">
                                {{ null !== optional($leave->user)->remaining_quota ? optional($leave->user)->remaining_quota . ' days' : '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm text-slate-400">Type</div>
                            <div class="font-semibold text-lg text-slate-800">
                                {{ ucfirst($leave->leave_type ?? ($leave->type ?? 'Leave')) }}</div>

                            <div class="mt-4 text-sm text-slate-400">Period</div>
                            <div class="font-medium text-slate-700">
                                {{ $leave->start_date ? \Illuminate\Support\Carbon::parse($leave->start_date)->format('d M Y') : '—' }}
                                —
                                {{ $leave->end_date ? \Illuminate\Support\Carbon::parse($leave->end_date)->format('d M Y') : '—' }}
                            </div>

                            <div class="mt-4 text-sm text-slate-400">Days</div>
                            <div class="font-medium text-slate-700">{{ $leave->days ?? '—' }}</div>

                            <div class="mt-4 text-sm text-slate-400">Apply Date</div>
                            <div class="font-medium text-slate-700">
                                {{ $leave->created_at ? \Illuminate\Support\Carbon::parse($leave->created_at)->format('d M Y') : '—' }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-end gap-3">
                        <div class="flex items-center gap-3">
                            @php
                                $current = auth()->user();
                                $sameDepartment = $current && $current->department === $leave->department;
                                $ownerIsApprover =
                                    optional($leave->user) &&
                                    method_exists($leave->user, 'hasAnyRole') &&
                                    $leave->user->hasAnyRole(['manager', 'supervisor', 'hod']);

                                $canSupervisorApprove =
                                    $current &&
                                    $current->hasRole('supervisor') &&
                                    !$current->hasAnyRole(['manager', 'hod', 'administrator', 'admin', 'hr']) &&
                                    $sameDepartment &&
                                    !$ownerIsApprover &&
                                    empty($leave->supervisor_approved_at);

                                $canSupervisorReject = $canSupervisorApprove;
                            @endphp

                            @if ($canSupervisorApprove)
                                <form method="POST" action="{{ route('approvals.approve', $leave->id) }}"
                                    onsubmit="return confirm('Approve this leave as supervisor?');">
                                    @csrf
                                    <input type="hidden" name="stage" value="supervisor">
                                    <input type="hidden" name="comment" value="Supervisor approved via detail">
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-md text-sm hover:bg-emerald-700">
                                        Approve as Supervisor
                                    </button>
                                </form>
                            @endif

                            @if ($canSupervisorReject)
                                <form method="POST" action="{{ route('approvals.reject', $leave->id) }}"
                                    onsubmit="return confirm('Reject this leave as supervisor?');">
                                    @csrf
                                    <input type="hidden" name="stage" value="supervisor">
                                    <input type="hidden" name="comment" value="Supervisor rejected via detail">
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-red-200 text-red-600 rounded-md text-sm hover:bg-red-50">
                                        Reject as Supervisor
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="text-sm text-slate-400">Reason</div>
                        <div class="mt-2 text-slate-800">{{ $leave->reason ?? '—' }}</div>
                    </div>

                    @if ($leave->mandatory_document)
                        <div class="mt-6">
                            <div class="text-sm text-slate-400">Mandatory Document</div>
                            <div class="mt-2 text-slate-800">{{ $leave->mandatory_document }}</div>
                        </div>
                    @endif

                    @if ($leave->attachment_path)
                        <div class="mt-6">
                            <div class="text-sm text-slate-400">Attachment</div>
                            <div class="mt-2 flex items-center gap-3">
                                <a href="{{ route('leave.preview', $leave->id) }}?attachment=1"
                                    class="text-indigo-600 underline font-medium">Open attachment</a>
                                <button x-data
                                    @click="$dispatch('open-preview', { url: '{{ route('leave.preview', $leave->id) }}?attachment=1' })"
                                    class="px-3 py-1 text-sm bg-white border border-gray-100 rounded shadow-sm hover:shadow focus:outline-none">Preview</button>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Approval timeline -->
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                    <h3 class="font-semibold mb-3 text-slate-700">Approval Timeline</h3>
                    <div id="approval-timeline" class="space-y-4">
                        @if ($leave->manager_approved_at)
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 mt-1">
                                    <div
                                        class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center">
                                        M</div>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold">Manager</div>
                                    <div class="text-sm text-slate-600">Status: <span class="font-medium">Approved</span>
                                    </div>
                                    @php
                                        // Prefer the recorded manager who approved; otherwise show expected manager
                                        $mgrName = $leave->manager?->name ?? null;
                                        if (!$mgrName && isset($expectedManager) && $expectedManager) {
                                            $mgrName = $expectedManager->name;
                                        }
                                    @endphp
                                    <div class="text-sm text-slate-500">By: {{ $mgrName ?? '—' }} · At:
                                        <time class="local-time" data-field="manager_approved_at"
                                            datetime="{{ \Illuminate\Support\Carbon::parse($leave->manager_approved_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($leave->manager_approved_at)->toDateTimeString() }}</time>
                                    </div>
                                    <div class="text-sm text-slate-500 mt-1">Comment: @php
                                        $mgrComment = $leave->manager_comment ?? null;
                                        if (
                                            $mgrComment &&
                                            \Illuminate\Support\Str::contains($mgrComment, 'manager_approve_leave')
                                        ) {
                                            echo '—';
                                        } else {
                                            echo $mgrComment ?: '—';
                                        }
                                    @endphp
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- If requester is a manager, show HOD-only --}}
                        @if (isset($isManagerRequester) && $isManagerRequester)
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 mt-1">
                                    <div
                                        class="w-10 h-10 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center">
                                        H</div>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold">HOD</div>
                                    <div class="text-sm text-slate-600">Status: <span
                                            class="font-medium">{{ $leave->manager_approved_at ? 'Approved' : ($leave->supervisor_approved_at ? 'Pending' : 'Waiting') }}</span>
                                    </div>
                                    <div class="text-sm text-slate-500">By:
                                        {{ $expectedHod?->name ?? ($leave->manager?->name ?? '—') }} · At:
                                        @if ($leave->manager_approved_at)
                                            <time class="local-time" data-field="manager_approved_at"
                                                datetime="{{ \Illuminate\Support\Carbon::parse($leave->manager_approved_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($leave->manager_approved_at)->toDateTimeString() }}</time>
                                        @else
                                            —
                                        @endif
                                    </div>

                                    @php $current = auth()->user(); @endphp
                                    {{-- If current user is HOD or HR/Admin, allow approving manager-requested leaves here --}}
                                    @if (
                                        ($current->hasAnyRole(['administrator', 'admin', 'hr']) ||
                                            ($current->hasRole('hod') && $current->department === $leave->department)) &&
                                            !$leave->manager_approved_at)
                                        <div class="mt-3">
                                            <form method="POST" action="{{ route('approvals.approve', $leave->id) }}"
                                                class="inline-block">
                                                @csrf
                                                <input type="hidden" name="comment" value="Approved via detail">
                                                <label class="text-sm text-slate-600 mr-2">Approve as</label>
                                                <select name="stage" class="rounded border px-2 py-1 text-sm">
                                                    <option value="hod">HOD</option>
                                                    <option value="manager">Manager</option>
                                                </select>
                                                <button type="submit"
                                                    class="inline-flex items-center gap-2 px-4 py-2 ml-3 bg-emerald-600 text-white rounded-md text-sm">Approve</button>
                                            </form>

                                            <form method="POST" action="{{ route('approvals.reject', $leave->id) }}"
                                                class="inline-block ml-2">
                                                @csrf
                                                <input type="hidden" name="comment" value="Rejected via detail">
                                                <label class="sr-only">Reject stage</label>
                                                <select name="stage" class="rounded border px-2 py-1 text-sm">
                                                    <option value="hod">HOD</option>
                                                    <option value="manager">Manager</option>
                                                </select>
                                                <button type="submit"
                                                    class="inline-flex items-center gap-2 px-3 py-2 ml-3 bg-white border rounded-md text-sm text-red-600">Reject</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if ($leave->final_status === 'approved' || $leave->hr_notified_at)
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 mt-1">
                                    <div
                                        class="w-10 h-10 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center">
                                        HR</div>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold">Final Status</div>
                                    <div class="text-sm text-slate-700">
                                        {{ ucfirst($leave->final_status ?? ($leave->status ?? 'pending')) }}</div>
                                    <div class="text-sm text-slate-500">HR Notified:
                                        @if ($leave->hr_notified_at)
                                            <time class="local-time" data-field="hr_notified_at"
                                                datetime="{{ \Illuminate\Support\Carbon::parse($leave->hr_notified_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($leave->hr_notified_at)->toDateTimeString() }}</time>
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Aside / Status -->
            <aside class="space-y-4">
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-slate-400">Current Status</div>
                            <div class="font-semibold text-lg text-slate-800">
                                <span
                                    id="status-text-aside">{{ ucfirst($leave->final_status ?? ($leave->status ?? 'pending')) }}</span>
                            </div>
                        </div>
                        <div>
                            @php
                                $st = strtolower(trim($leave->final_status ?? ($leave->status ?? 'pending')));
                                $badgeClass = 'bg-gray-100 text-gray-700';
                                if (in_array($st, ['approved', 'accept', 'accepted'])) {
                                    $badgeClass = 'bg-emerald-100 text-emerald-700';
                                } elseif (in_array($st, ['rejected', 'denied', 'deny'])) {
                                    $badgeClass = 'bg-rose-100 text-rose-700';
                                } elseif ($st === 'pending') {
                                    $badgeClass = 'bg-yellow-100 text-yellow-800';
                                }
                            @endphp
                            <span id="status-badge-aside"
                                class="px-3 py-1 rounded-full text-sm {{ $badgeClass }} shadow-sm">{{ ucfirst($st) }}</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Expose small dynamic values to the scripts below without embedding Blade inside large JS blocks
        window._leave = {
            id: <?php echo json_encode($leave->id); ?>,
            serverTimeUrl: <?php echo json_encode(route('server.time')); ?>
        };
    </script>

    @verbatim
        <script>
            // Small Alpine-less event listener to open a preview modal dispatched from the page
            document.addEventListener('open-preview', function(e) {
                var url = e.detail && e.detail.url ? e.detail.url : null;
                if (!url) return;
                var modal = document.getElementById('attachment-preview-modal');
                if (!modal) {
                    modal = document.createElement('div');
                    modal.id = 'attachment-preview-modal';
                    modal.style.position = 'fixed';
                    modal.style.inset = '0';
                    modal.style.zIndex = '1200';
                    modal.style.display = 'flex';
                    modal.style.alignItems = 'center';
                    modal.style.justifyContent = 'center';
                    modal.innerHTML = `
                    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);"></div>
                    <div style="width:90%;height:90%;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 20px 48px rgba(2,6,23,.3)">
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px;border-bottom:1px solid #eee">
                            <div style="font-weight:600">Attachment Preview</div>
                            <div><a id="ap-download" href="${url}" target="_blank" style="margin-right:8px" class="inline-block px-3 py-1 bg-indigo-600 text-white rounded">Open</a><button id="ap-close" style="padding:6px 10px;border-radius:6px">Close</button></div>
                        </div>
                        <iframe src="${url}" style="width:100%;height:calc(100% - 54px);border:0"></iframe>
                    </div>`;
                    document.body.appendChild(modal);
                    modal.querySelector('#ap-close').addEventListener('click', function() {
                        modal.remove();
                    });
                } else {
                    var iframe = modal.querySelector('iframe');
                    iframe.src = url;
                    modal.style.display = 'flex';
                }
            });
        </script>

        <script>
            // Realtime client clock and local timestamp renderer
            (function() {
                function pad(v) {
                    return v.toString().padStart(2, '0');
                }

                const clockEl = document.getElementById('realtime-clock');
                const dateEl = document.getElementById('realtime-date');
                let serverOffset = 0;
                try {
                    const serverIso = (clockEl && clockEl.dataset && clockEl.dataset.serverTime) ? clockEl.dataset
                        .serverTime : ((dateEl && dateEl.dataset && dateEl.dataset.serverTime) ? dateEl.dataset.serverTime :
                            null);
                    if (serverIso) {
                        const serverDate = new Date(serverIso);
                        const clientDate = new Date();
                        if (!isNaN(serverDate.getTime())) {
                            serverOffset = serverDate.getTime() - clientDate.getTime();
                        }
                    }
                } catch (e) {
                    serverOffset = 0;
                }

                function updateClock() {
                    const now = new Date(Date.now() + serverOffset);
                    const h = pad(now.getHours());
                    const m = pad(now.getMinutes());
                    const s = pad(now.getSeconds());
                    if (clockEl) clockEl.textContent = h + ':' + m + ':' + s;
                    if (dateEl) {
                        const df = new Intl.DateTimeFormat(undefined, {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                        dateEl.textContent = df.format(now);
                    }
                }

                window.renderLocalTimes = function renderLocalTimes() {
                    const nodes = document.querySelectorAll('time.local-time');
                    nodes.forEach(function(node) {
                        const iso = node.getAttribute('datetime');
                        if (!iso) return;
                        const d = new Date(iso);
                        if (isNaN(d.getTime())) return;
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

                updateClock();
                renderLocalTimes();

                async function resyncServer() {
                    try {
                        const res = await fetch(window._leave.serverTimeUrl, {
                            credentials: 'same-origin'
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        if (data && data.now) {
                            const serverDate = new Date(data.now);
                            const clientDate = new Date();
                            if (!isNaN(serverDate.getTime())) {
                                serverOffset = serverDate.getTime() - clientDate.getTime();
                            }
                        }
                    } catch (e) {
                        // ignore network errors
                    }
                }

                resyncServer();
                setInterval(resyncServer, 5 * 60 * 1000);
                setInterval(updateClock, 1000);
                setInterval(renderLocalTimes, 60 * 1000);
            })
            ();

            // Subscribe to leave-specific approval events and update timeline
            (function() {
                try {
                    if (window.Echo && typeof window.Echo.private === 'function') {
                        const ch = window.Echo.private('leave.' + window._leave.id);
                        ch.listen('ApprovalCreated', function(e) {
                            try {
                                const container = document.getElementById('approval-timeline');
                                if (!container) return;
                                const wrapper = document.createElement('div');
                                wrapper.className = 'flex items-start gap-4';
                                const approverInitial = (e.approver_name && e.approver_name.length > 0) ? e
                                    .approver_name.charAt(0).toUpperCase() : 'U';
                                wrapper.innerHTML = `
                                <div class="flex-shrink-0 mt-1">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center">${approverInitial}</div>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold">${e.approver_name ?? 'Approver'}</div>
                                    <div class="text-sm text-slate-600">Status: <span class="font-medium">${e.action ?? 'updated'}</span></div>
                                    <div class="text-sm text-slate-500">By: ${e.approver_name ?? '—'} · At: <time class="local-time" datetime="${e.created_at}">${e.created_at}</time></div>
                                    <div class="text-sm text-slate-500 mt-1">Comment: ${e.comment ?? '—'}</div>
                                </div>
                            `;
                                container.insertBefore(wrapper, container.firstChild);
                                if (typeof window.renderLocalTimes === 'function') window.renderLocalTimes();
                                // Update quota/remaining if present in the payload
                                try {
                                    if (e.remaining_quota !== undefined && document.getElementById(
                                            'user-remaining-quota')) {
                                        document.getElementById('user-remaining-quota').textContent = e
                                            .remaining_quota;
                                    }
                                    if (e.quota !== undefined && document.getElementById('user-quota')) {
                                        document.getElementById('user-quota').textContent = e.quota;
                                    }

                                    // Update top and aside status badges/timestamps
                                    if (e.final_status !== undefined) {
                                        var st = (e.final_status || (e.action === 'approved' ? 'approved' :
                                            'pending')).toString().toLowerCase();
                                        var top = document.getElementById('status-badge-top');
                                        var aside = document.getElementById('status-badge-aside');
                                        var statusText = document.getElementById('status-text-aside');
                                        if (top) {
                                            top.textContent = st.charAt(0).toUpperCase() + st.slice(1);
                                            top.className = 'px-4 py-2 rounded-full text-sm ' + (st === 'pending' ?
                                                'bg-yellow-100 text-yellow-800' : (st === 'approved' ?
                                                    'bg-emerald-100 text-emerald-700' :
                                                    'bg-rose-100 text-rose-700'));
                                        }
                                        if (aside) {
                                            aside.textContent = st.charAt(0).toUpperCase() + st.slice(1);
                                            aside.className = 'px-3 py-1 rounded-full text-sm ' + (st ===
                                                'pending' ? 'bg-yellow-100 text-yellow-800' : (st ===
                                                    'approved' ? 'bg-emerald-100 text-emerald-700' :
                                                    'bg-rose-100 text-rose-700')) + ' shadow-sm';
                                        }
                                        if (statusText) {
                                            statusText.textContent = st.charAt(0).toUpperCase() + st.slice(1);
                                        }
                                    }
                                    // Update timestamps if provided
                                    if (e.supervisor_approved_at) {
                                        var node = document.querySelector(
                                            'time.local-time[data-field="supervisor_approved_at"]');
                                        if (node) {
                                            node.setAttribute('datetime', e.supervisor_approved_at);
                                            node.textContent = e.supervisor_approved_at;
                                        }
                                    }
                                    if (e.manager_approved_at) {
                                        var node = document.querySelector(
                                            'time.local-time[data-field="manager_approved_at"]');
                                        if (node) {
                                            node.setAttribute('datetime', e.manager_approved_at);
                                            node.textContent = e.manager_approved_at;
                                        }
                                    }
                                    if (e.hr_notified_at) {
                                        var node = document.querySelector(
                                            'time.local-time[data-field="hr_notified_at"]');
                                        if (node) {
                                            node.setAttribute('datetime', e.hr_notified_at);
                                            node.textContent = e.hr_notified_at;
                                        }
                                    }
                                } catch (updErr) {
                                    // non-fatal
                                }
                            } catch (err) {
                                console.error('Failed to render realtime approval', err);
                            }
                        });
                    }
                } catch (e) {
                    // ignore
                }
            })();
        </script>
    @endverbatim
@endpush
