@php
    use Illuminate\Support\Facades\Schema;

    $unreadCount = 0;
    $recent = collect();

    if (auth()->check() && Schema::hasTable('notifications')) {
        $user = auth()->user();

        // Base queries
        $allNotifications = $user->notifications();
        $unreadNotificationsQuery = $user->notifications()->whereNull('read_at');

        // Role-specific filtering
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['administrator', 'admin', 'hr'])) {
            // HR/Admin: show all incoming leave notifications and approved notifications
            $types = [
                \App\Notifications\NewLeaveForApprover::class,
                \App\Notifications\NewLeaveSubmitted::class,
                \App\Notifications\LeaveFullyApproved::class,
                \App\Notifications\LeaveStatusChanged::class,
                \App\Notifications\SupervisorApproved::class,
            ];

            // Load and dedupe notifications by leave id so we don't show duplicates for the same leave
        $unreadItems = $unreadNotificationsQuery->whereIn('type', $types)->get();
        $recentItems = $allNotifications->whereIn('type', $types)->latest()->take(30)->get();

        $dedupFn = function ($n) {
            $d = (array) $n->data;
            return $d['leave_id'] ?? ($d['id'] ?? $n->id);
        };

        $uniqueRecent = collect($recentItems)->unique($dedupFn)->values();
        $recent = $uniqueRecent->take(6);
        $unreadCount = collect($unreadItems)->unique($dedupFn)->count();
    } elseif (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['manager', 'supervisor', 'hod'])) {
        // Manager/HOD/Supervisor: show all unread notifications (not limited by type)
        // Managers should see any incoming leave notifications and other relevant alerts
        // For managers/supervisors show latest unread notifications, dedup by leave id
        $unreadItems = $unreadNotificationsQuery->get();
        $recentItems = $allNotifications->latest()->take(30)->get();
        $dedupFn = function ($n) {
            $d = (array) $n->data;
            return $d['leave_id'] ?? ($d['id'] ?? $n->id);
        };
        $recent = collect($recentItems)->unique($dedupFn)->values()->take(6);
        $unreadCount = collect($unreadItems)->unique($dedupFn)->count();
    } else {
        // Employee (default): show approvals relevant to the employee
        // SupervisorApproved and any LeaveStatusChanged where status == 'approved' or LeaveFullyApproved
        $recentQuery = $allNotifications->where(function ($q) {
            $q->where('type', \App\Notifications\SupervisorApproved::class)
                ->orWhere('type', \App\Notifications\LeaveFullyApproved::class)
                ->orWhere(function ($q2) {
                    $q2->where('type', \App\Notifications\LeaveStatusChanged::class)->where(
                        'data->status',
                        'approved',
                    );
                });
        });

        $unreadQuery = $unreadNotificationsQuery->where(function ($q) {
            $q->where('type', \App\Notifications\SupervisorApproved::class)
                ->orWhere('type', \App\Notifications\LeaveFullyApproved::class)
                ->orWhere(function ($q2) {
                    $q2->where('type', \App\Notifications\LeaveStatusChanged::class)->where(
                        'data->status',
                        'approved',
                    );
                });
        });

        // Execute and dedupe results by leave id to avoid duplicate notifications
        $recentItems = $recentQuery->latest()->get();
        $unreadItems = $unreadQuery->get();
        $dedupFn = function ($n) {
            $d = (array) $n->data;
            return $d['leave_id'] ?? ($d['id'] ?? $n->id);
            };
            $recent = collect($recentItems)->unique($dedupFn)->values()->take(6);
            $unreadCount = collect($unreadItems)->unique($dedupFn)->count();
        }
    }
@endphp

<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" class="relative inline-flex items-center p-2 rounded-md hover:bg-slate-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-700" viewBox="0 0 24 24" fill="none"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        @if ($unreadCount > 0)
            <span
                class="notification-badge absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-semibold leading-none text-white bg-rose-500 rounded-full">{{ $unreadCount }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak @click.away="open = false" x-transition
        class="origin-top-right absolute right-0 mt-2 w-80 rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 z-50">
        <div class="p-3 border-b">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold">Notifications</div>
                <div class="text-xs text-slate-500">{{ $unreadCount }} unread</div>
            </div>
        </div>

        <div class="max-h-64 overflow-y-auto">
            @forelse($recent as $n)
                @php $data = (array) $n->data; @endphp
                <div data-id="{{ $n->id }}"
                    class="px-3 py-2 hover:bg-slate-50 border-b last:border-b-0 flex items-start gap-3">
                    <div class="flex-1">
                        @php
                            $role =
                                $data['approver_role'] ??
                                (isset($data['supervisor'])
                                    ? 'supervisor'
                                    : (isset($data['manager'])
                                        ? 'manager'
                                        : null));
                            $approverName = $data['approver'] ?? ($data['supervisor'] ?? ($data['manager'] ?? null));
                            $roleColors = [
                                'manager' => 'bg-amber-100 text-amber-800',
                                'supervisor' => 'bg-emerald-100 text-emerald-800',
                                'approver' => 'bg-sky-100 text-sky-800',
                                'hr' => 'bg-violet-100 text-violet-800',
                            ];
                            $badgeClass = $role ? $roleColors[$role] ?? 'bg-slate-100 text-slate-800' : null;

                            $required = $data['required_approvals'] ?? null;
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
                            <div class="text-sm text-slate-800">{{ $data['message'] ?? class_basename($n->type) }}</div>
                            @if (isset($data['employee']) || isset($data['leave_type']))
                                <div class="text-xs text-slate-500 mt-1">
                                    @if (isset($data['employee']))
                                        <strong>{{ $data['employee'] }}</strong>
                                    @endif
                                    @if (isset($data['leave_type']))
                                        <span class="ml-2">· {{ ucfirst($data['leave_type']) }}</span>
                                    @endif
                                    @if (isset($data['days']))
                                        <span class="ml-2">· {{ $data['days'] }} day(s)</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="text-xs text-slate-500 mt-1 flex items-center gap-2">
                            @if (isset($data['start_date']))
                                <span>{{ $data['start_date'] }}</span>
                            @endif
                            @if (isset($data['end_date']))
                                <span>— {{ $data['end_date'] }}</span>
                            @endif
                            @if ($approverName)
                                <span class="text-xs text-slate-600">by {{ $approverName }}</span>
                            @endif
                            {{-- show created at timestamp for realtime rendering --}}
                            <span class="text-xs text-slate-400">· <time class="local-time"
                                    datetime="{{ \Illuminate\Support\Carbon::parse($n->created_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($n->created_at)->toDateTimeString() }}</time></span>
                        </div>

                        <div class="mt-2 text-xs">
                            <button data-id="{{ $n->id }}" data-type="{{ $n->type }}"
                                data-info='@json($data)'
                                class="mark-read text-blue-600 hover:underline">Mark as read</button>
                            @php $openUrl = $data['url'] ?? (isset($data['leave_id']) ? url('/approvals/' . $data['leave_id']) : null); @endphp
                            @if ($openUrl)
                                <a href="{{ $openUrl }}"
                                    class="open-target ml-3 text-xs text-slate-600 hover:underline">Open</a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-4 text-sm text-slate-500">No notifications yet.</div>
            @endforelse
        </div>

        <div class="p-2 border-t flex items-center justify-between">
            <button id="markAllRead" class="text-xs text-slate-600 hover:underline">Mark all as read</button>
            <a href="{{ route('notifications.index') }}" class="text-xs text-slate-600 hover:underline">View all</a>
        </div>
    </div>

    <script>
        (function() {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            function postJson(url, body) {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify(body || {})
                }).then(r => r.json().catch(() => ({}))).catch(() => ({}));
            }

            const listContainer = document.querySelector('.max-h-64');
            const bellButton = document.querySelector('[x-data] > button');
            const headerUnread = document.querySelector('.p-3 .text-xs.text-slate-500');

            function updateBadge(delta) {
                try {
                    let badge = document.querySelector('.notification-badge');
                    if (!badge && delta > 0 && bellButton) {
                        badge = document.createElement('span');
                        badge.className =
                            'notification-badge absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-semibold leading-none text-white bg-rose-500 rounded-full';
                        badge.textContent = delta;
                        bellButton.appendChild(badge);
                    } else if (badge) {
                        const current = parseInt(badge.textContent || '0', 10);
                        const updated = Math.max(0, current + delta);
                        if (updated <= 0) {
                            badge.remove();
                        } else {
                            badge.textContent = updated;
                        }
                    }

                    if (headerUnread) {
                        const currentHeader = parseInt((headerUnread.textContent || '0').split(' ')[0], 10) || 0;
                        const newHeader = Math.max(0, currentHeader + delta);
                        headerUnread.textContent = newHeader + ' unread';
                    }
                } catch (e) {
                    // ignore DOM update errors
                }
            }

            function clearNoNotificationsIfNeeded() {
                if (!listContainer) return;
                const emptyNode = Array.from(listContainer.children).find(c => c.textContent && c.textContent.trim() ===
                    'No notifications yet.');
                if (emptyNode) emptyNode.remove();
            }

            function ensureLimit(maxItems) {
                if (!listContainer) return;
                const items = Array.from(listContainer.querySelectorAll('[data-id]'));
                while (items.length > maxItems) {
                    const last = items.pop();
                    if (last) last.remove();
                }
            }

            function buildNotificationElement(n) {
                const id = n.id || n.notification_id || null;
                const type = n.type || '';
                const data = n.data || {};

                const role = data.approver_role || (data.supervisor ? 'supervisor' : (data.manager ? 'manager' : null));
                const approverName = data.approver || data.supervisor || data.manager || null;

                const roleColors = {
                    manager: 'bg-amber-100 text-amber-800',
                    supervisor: 'bg-emerald-100 text-emerald-800',
                    approver: 'bg-sky-100 text-sky-800',
                    hr: 'bg-violet-100 text-violet-800',
                };

                const outer = document.createElement('div');
                if (id) outer.setAttribute('data-id', id);
                outer.className = 'px-3 py-2 hover:bg-slate-50 border-b last:border-b-0 flex items-start gap-3';

                const inner = document.createElement('div');
                inner.className = 'flex-1';

                // top row: role badge + message
                const top = document.createElement('div');
                top.className = 'flex items-center gap-2';

                if (role) {
                    const span = document.createElement('span');
                    const badgeCls = roleColors[role] || 'bg-slate-100 text-slate-800';
                    span.className = 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ' +
                        badgeCls;
                    span.textContent = role.charAt(0).toUpperCase() + role.slice(1);
                    top.appendChild(span);
                }

                const msg = document.createElement('div');
                msg.className = 'text-sm text-slate-800';
                msg.textContent = data.message || (type ? type.split('\\').pop() : 'Notification');
                top.appendChild(msg);

                inner.appendChild(top);

                // dates / approver
                const meta = document.createElement('div');
                meta.className = 'text-xs text-slate-500 mt-1 flex items-center gap-2';
                if (data.start_date) {
                    const s = document.createElement('span');
                    s.textContent = data.start_date;
                    meta.appendChild(s);
                }
                if (data.end_date) {
                    const e = document.createElement('span');
                    e.textContent = '— ' + data.end_date;
                    meta.appendChild(e);
                }
                if (approverName) {
                    const a = document.createElement('span');
                    a.className = 'text-xs text-slate-600';
                    a.textContent = 'by ' + approverName;
                    meta.appendChild(a);
                }
                // include created_at if present in payload (Echo notifications often include created_at)
                if (n.created_at) {
                    const ta = document.createElement('span');
                    ta.className = 'text-xs text-slate-400';
                    ta.appendChild(document.createTextNode('\u00B7 '));
                    const timeEl = document.createElement('time');
                    timeEl.className = 'local-time';
                    timeEl.setAttribute('datetime', n.created_at);
                    timeEl.textContent = n.created_at;
                    ta.appendChild(timeEl);
                    meta.appendChild(ta);
                }
                inner.appendChild(meta);

                // actions
                const actions = document.createElement('div');
                actions.className = 'mt-2 text-xs';

                const markBtn = document.createElement('button');
                if (id) markBtn.setAttribute('data-id', id);
                markBtn.setAttribute('data-type', type);
                markBtn.setAttribute('data-info', JSON.stringify(data));
                markBtn.className = 'mark-read text-blue-600 hover:underline';
                markBtn.textContent = 'Mark as read';
                actions.appendChild(markBtn);

                if (data.leave_id || data.id) {
                    const a = document.createElement('a');
                    if (id) a.setAttribute('data-id', id);
                    a.setAttribute('data-type', type);
                    a.setAttribute('data-info', JSON.stringify(data));
                    a.href = '#';
                    a.className = 'open-target ml-3 text-xs text-slate-600 hover:underline';
                    a.textContent = 'Open';
                    actions.appendChild(a);
                }

                inner.appendChild(actions);
                outer.appendChild(inner);

                return outer;
            }

            // delegated click handlers for mark-read and open-target
            if (listContainer) {
                listContainer.addEventListener('click', function(e) {
                    const mark = e.target.closest('.mark-read');
                    if (mark) {
                        e.preventDefault();
                        const id = mark.dataset.id;
                        postJson('/notifications/' + id + '/read').then(() => {
                            // decrement badge and remove DOM row
                            updateBadge(-1);
                            const row = listContainer.querySelector('[data-id="' + id + '"]');
                            if (row) row.remove();
                            // if no notification left, show placeholder
                            if (!listContainer.querySelector('[data-id]')) {
                                const empty = document.createElement('div');
                                empty.className = 'p-4 text-sm text-slate-500';
                                empty.textContent = 'No notifications yet.';
                                listContainer.appendChild(empty);
                            }
                        }).catch(() => {});
                        return;
                    }

                    const open = e.target.closest('.open-target');
                    if (open) {
                        e.preventDefault();
                        const id = open.dataset.id;
                        const info = open.dataset.info ? JSON.parse(open.dataset.info) : {};
                        postJson('/notifications/' + id + '/read').finally(() => {
                            const leaveId = info.leave_id || info.id || info.leaveId || null;
                            if (!leaveId) return;
                            window.location.href = '/approvals/' + leaveId;
                        });
                        return;
                    }
                });
            }

            // mark all read (DOM-only update after request)
            document.getElementById('markAllRead')?.addEventListener('click', function(e) {
                e.preventDefault();
                postJson('/notifications/mark-all-read').then(() => {
                    // clear badge and show placeholder
                    const badge = document.querySelector('.notification-badge');
                    if (badge) badge.remove();
                    if (headerUnread) headerUnread.textContent = '0 unread';
                    if (listContainer) {
                        listContainer.innerHTML =
                            '<div class="p-4 text-sm text-slate-500">No notifications yet.</div>';
                    }
                }).catch(() => {});
            });

            // Realtime insert: if Echo available, listen and prepend into dropdown
            try {
                if (window.Echo && window.Laravel && window.Laravel.userId) {
                    window.Echo.private('App.Models.User.' + window.Laravel.userId)
                        .notification(function(n) {
                            try {
                                // Build element from payload and prepend
                                if (!listContainer) return;
                                clearNoNotificationsIfNeeded();
                                const el = buildNotificationElement(n);
                                listContainer.prepend(el);
                                if (typeof window.renderLocalTimes === 'function') window.renderLocalTimes();
                                updateBadge(1);
                                ensureLimit(6);
                            } catch (e) {
                                // fallback: small reload
                                try {
                                    location.reload();
                                } catch (e2) {}
                            }
                        });
                }
            } catch (e) {
                // noop
            }

            // Render local times for any existing notifications in the dropdown.
            try {
                if (typeof window.renderLocalTimes === 'function') {
                    window.renderLocalTimes();
                } else {
                    // fallback simple renderer: show local toLocaleString()
                    const nodes = document.querySelectorAll('time.local-time');
                    nodes.forEach(function(node) {
                        const iso = node.getAttribute('datetime');
                        if (!iso) return;
                        const d = new Date(iso);
                        if (isNaN(d.getTime())) return;
                        node.textContent = d.toLocaleString();
                        node.title = d.toString();
                    });
                }
            } catch (e) {
                // ignore
            }
        })();
    </script>
</div>
