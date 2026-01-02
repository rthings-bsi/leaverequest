@php
    // The employee dashboard partial expects $leaveRequests collection and optional quota/used values
    // If the parent view did not pass $leaveRequests, fall back to querying the DB so the
    // partial always shows live data (useful when included from different controllers).
    use App\Models\LeaveRequest;
    use Carbon\Carbon;

    if (!isset($leaveRequests)) {
        try {
            $leaveRequests = LeaveRequest::with('user')->latest()->get();
        } catch (\Throwable $e) {
            // If the model is missing or DB is unreachable, fall back to an empty collection
            $leaveRequests = collect();
        }
    }
@endphp

<style>
    /* Paste the CSS and JS from the employee design (trimmed to essentials) */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap');

    :root {
        --dash-radius: 14px;
        --dash-accent: #7c3aed;
        --dash-card-pad: 1.25rem
    }

    .dashboard-wrapper {
        font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
        color: #0b1220
    }

    .hero-title {
        font-size: 2rem
    }

    .hero-clock .text-3xl {
        font-size: 2.6rem;
        font-weight: 800
    }

    .dash-hero {
        background: linear-gradient(180deg, #83A4D4 60%, #B6FBFF 100%);
        padding: 2.25rem 2rem;
        min-height: 160px;
        border-radius: 18px;
        box-shadow: 0 20px 48px rgba(8, 20, 40, .12)
    }

    .stat-card {
        cursor: pointer;
        transition: transform .18s cubic-bezier(.22, .9, .32, 1), box-shadow .18s ease, border-color .18s ease;
        will-change: transform, box-shadow;
        padding: var(--dash-card-pad);
        border-radius: var(--dash-radius);
        background: linear-gradient(180deg, #ffffff, #f3f7fb);
        box-shadow: 0 8px 28px rgba(2, 6, 23, .09)
    }

    .stat-card .stat-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 6px 14px rgba(2, 6, 23, .04)
    }

    .stat-card .text-3xl {
        font-size: 1.9rem
    }

    .stat-card h3,
    .stat-card .text-3xl,
    .stat-card .text-sm {
        color: #0b1220 !important
    }

    .stat-card.pending h3,
    .stat-card.pending .text-3xl,
    .stat-card.pending .text-sm {
        color: #991b1b !important
    }

    .stat-card.approved h3,
    .stat-card.approved .text-3xl,
    .stat-card.approved .text-sm {
        color: #04603f !important
    }

    .stat-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 48px rgba(15, 23, 42, .12), 0 6px 18px rgba(15, 23, 42, .06)
    }

    .mini-progress {
        height: 16px;
        background: rgba(15, 23, 42, .04);
        border-radius: 999px;
        overflow: hidden;
        box-shadow: 0 6px 18px rgba(2, 6, 23, .04) inset
    }

    .mini-progress .mini-fill {
        height: 100%;
        border-radius: 999px;
        transition: width .6s cubic-bezier(.2, .9, .3, 1);
        box-shadow: 0 8px 20px rgba(2, 6, 23, .06) inset
    }

    .leave-panel {
        background: linear-gradient(180deg, #d6e7fb 0%, #e6f1f7 100%) !important;
        color: #071225 !important;
        border-radius: 14px;
        box-shadow: 0 14px 40px rgba(14, 30, 37, .08);
        padding: 20px
    }

    /* ensure panels have comfortable padding on small screens */
    .leave-panel,
    .stat-card {
        padding: 1rem !important;
    }

    .leave-panel h3,
    .leave-panel .font-semibold,
    .leave-panel .text-sm,
    .leave-panel .text-gray-600 {
        color: #071225 !important
    }

    .leave-panel .rounded-md {
        background: transparent !important
    }

    .leave-panel .px-2.py-1.rounded-md.text-red-700 {
        background: rgba(255, 255, 255, .75) !important;
        color: #b91c1c !important
    }

    .leave-panel .px-2.py-1.rounded-md.text-green-700 {
        background: rgba(255, 255, 255, .75) !important;
        color: #065f46 !important
    }

    /* small responsive tweaks */
    @media (max-width:768px) {
        .dash-hero {
            padding: 1rem;
            border-radius: 12px
        }

        .hero-title {
            font-size: 1.6rem !important
        }
    }
</style>

@unless (request()->routeIs('leave.*') || request()->routeIs('approvals.*'))
    <div class="dashboard-wrapper max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <section class="mb-6">
            <div class="dash-hero p-4 sm:p-6 rounded-xl">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="hero-title text-2xl sm:text-3xl font-extrabold text-gray-900">Dashboard - Time Request
                        </h1>
                        <p class="text-sm text-gray-700 mt-1">Your personal leave overview</p>
                    </div>

                    <div class="hero-clock text-right ml-auto">
                        <div class="text-3xl font-extrabold text-gray-900" id="dashboard-clock">--:--:--</div>
                        <div class="text-sm text-gray-700" id="dashboard-date">--</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @php
                $totalRequests = $leaveRequests->count();
                $today = \Carbon\Carbon::today();
                $todayRequests = $leaveRequests
                    ->filter(function ($l) use ($today) {
                        return optional($l->start_date)->toDateString() === $today->toDateString();
                    })
                    ->count();
                $pendingCount = $leaveRequests
                    ->filter(fn($l) => strtolower(trim($l->final_status ?? ($l->status ?? ''))) === 'pending')
                    ->count();
                $approvedCount = $leaveRequests
                    ->filter(
                        fn($l) => in_array(strtolower(trim($l->final_status ?? ($l->status ?? ''))), [
                            'approved',
                            'accept',
                            'accepted',
                        ]),
                    )
                    ->count();
                $rejectedCount = $leaveRequests
                    ->filter(
                        fn($l) => in_array(strtolower(trim($l->final_status ?? ($l->status ?? ''))), [
                            'rejected',
                            'denied',
                            'deny',
                        ]),
                    )
                    ->count();
                $employeesCount = $leaveRequests->pluck('user_id')->filter()->unique()->count() ?: 0;
                $departmentsCount = $leaveRequests->pluck('department')->filter()->unique()->count() ?: 0;
                $max = max([
                    $totalRequests,
                    $todayRequests,
                    $pendingCount,
                    $approvedCount,
                    $rejectedCount,
                    $employeesCount,
                    $departmentsCount,
                    1,
                ]);
                $formatDays = fn($n) => $n === null || $n === ''
                    ? '—'
                    : (floor($n) == $n
                        ? (int) $n . ' days'
                        : number_format($n, 1, ',', '.') . ' hari');
                $cycleStart = null;
                $cycleEnd = null;
                // If quota/used/remaining not passed by controller, compute from the authenticated user's data
if (!isset($quota) || !isset($used) || !isset($remaining)) {
    $user = auth()->user();
    // default quota from config if user doesn't have custom quota
                    $quota = $user && isset($user->quota) ? $user->quota : config('leave.quota', 12);

                    // compute used days in the current leave cycle for this user
                    $used = 0;
                    try {
                        if ($user) {
                            if (method_exists($user, 'leaveCycleRange')) {
                                [$cycleStart, $cycleEnd] = $user->leaveCycleRange();
                            }

                            if (method_exists($user, 'usedLeaveDaysInCurrentCycle')) {
                                $used = $user->usedLeaveDaysInCurrentCycle();
                            } else {
                                $rangeStart = $cycleStart ?: \Carbon\Carbon::now()->startOfYear();
                                $rangeEnd = $cycleEnd ?: \Carbon\Carbon::now()->endOfYear();
                                $used = \App\Models\LeaveRequest::where('user_id', $user->id)
                                    ->where(function ($q) {
                                        $q->where('final_status', 'approved')->orWhere('status', 'approved');
                                    })
                                    ->whereBetween('start_date', [
                                        $rangeStart->toDateString(),
                                        $rangeEnd->toDateString(),
                                    ])
                                    ->sum('days');
                            }
                        }
                    } catch (\Throwable $e) {
                        $used = 0;
                    }

                    $remaining = max(0, ($quota ?? 0) - ($used ?? 0));
                }

                if (!$cycleStart && auth()->check() && method_exists(auth()->user(), 'leaveCycleRange')) {
                    try {
                        [$cycleStart, $cycleEnd] = auth()->user()->leaveCycleRange();
                    } catch (\Throwable $e) {
                        $cycleStart = null;
                        $cycleEnd = null;
                    }
                }
            @endphp

            <div class="stat-card today">
                <div class="flex items-start gap-3">
                    <div class="stat-icon bg-white">📦</div>
                    <div class="flex-1">
                        <h3 class="font-semibold">Today's Requests</h3>
                        <div class="mini-progress mt-3">
                            <div class="mini-fill bg-cyan-400"
                                style="width: {{ $max ? round(($todayRequests / $max) * 100) : 0 }}%"></div>
                        </div>
                        <div class="mt-4 text-3xl font-bold">{{ $todayRequests }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ now()->format('d M Y') }}</div>
                    </div>
                </div>
            </div>

            <div class="stat-card pending">
                <div class="flex items-start gap-3">
                    <div class="stat-icon bg-white">⏳</div>
                    <div class="flex-1">
                        <h3 class="font-semibold">Pending</h3>
                        <div class="mini-progress mt-3">
                            <div class="mini-fill bg-red-400"
                                style="width: {{ $max ? round(($pendingCount / $max) * 100) : 0 }}%">
                            </div>
                        </div>
                        <div class="mt-4 text-3xl font-bold">{{ $pendingCount }}</div>
                        <div class="text-sm text-gray-500 mt-1">Waiting Processing</div>
                    </div>
                </div>
            </div>

            <div class="stat-card approved">
                <div class="flex items-start gap-3">
                    <div class="stat-icon bg-white">✓</div>
                    <div class="flex-1">
                        <h3 class="font-semibold">Approved</h3>
                        <div class="mini-progress mt-3">
                            <div class="mini-fill bg-emerald-500"
                                style="width: {{ $max ? round(($approvedCount / $max) * 100) : 0 }}%"></div>
                        </div>
                        <div class="mt-4 text-3xl font-bold">{{ $approvedCount }}</div>
                        <div class="text-sm text-gray-500 mt-1">Approved Leave</div>
                    </div>
                </div>
            </div>

            <div class="stat-card rejected">
                <div class="flex items-start gap-3">
                    <div class="stat-icon bg-white">✕</div>
                    <div class="flex-1">
                        <h3 class="font-semibold">Rejected</h3>
                        <div class="mini-progress mt-3">
                            <div class="mini-fill bg-pink-300"
                                style="width: {{ $max ? round(($rejectedCount / $max) * 100) : 0 }}%"></div>
                        </div>
                        <div class="mt-4 text-3xl font-bold">{{ $rejectedCount }}</div>
                        <div class="text-sm text-gray-500 mt-1">Rejected Leave</div>
                    </div>
                </div>
            </div>
        </section>

    </div>
@endunless

</section>

<section class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="leave-panel">
        <div class="flex flex-col lg:flex-row gap-6">
            <div class="lg:w-1/3">
                <h3 class="text-sm font-medium text-slate-600">Your remaining leave quota</h3>
                <div class="mt-3 text-4xl font-extrabold text-slate-900">{{ $formatDays($remaining ?? 0) }}</div>
                <div class="text-sm text-slate-500 mt-1">Quota this cycle: {{ $formatDays($quota ?? 0) }}</div>
                @if ($cycleStart && $cycleEnd)
                    <div class="text-xs text-slate-500 mt-1">Cycle: {{ $cycleStart->format('d M Y') }} –
                        {{ $cycleEnd->format('d M Y') }}</div>
                @endif

                <div class="mt-6">
                    <div class="mini-stat ml-0 bg-white shadow p-3 rounded-lg inline-block sm:float-right">
                        <div class="mini-title">🌱 Go Green</div>
                        <div class="mini-number">Saved {{ $totalRequests }} sheets</div>
                        <div class="text-xs mt-1">~{{ number_format($totalRequests / 100, 2) }} small trees</div>
                    </div>
                    <div class="progress-strip" role="progressbar"
                        aria-valuenow="{{ round($max ? (($used ?? 0) / ($quota ?? 1)) * 100 : 0) }}" aria-valuemin="0"
                        aria-valuemax="100" aria-label="Quota used">
                        <div class="progress-fill"
                            style="width: {{ $quota ? max(0, min(100, (($used ?? 0) / ($quota ?? 1)) * 100)) : 0 }}%; background: linear-gradient(90deg,#7c3aed,#06b6d4);">
                        </div>
                    </div>

                </div>
            </div>

            <div class="lg:w-2/3">
                <h4 class="text-base font-semibold mb-2">Your leave request</h4>
                @if ($leaveRequests->isEmpty())
                    <div class="flex items-center gap-3 text-sm text-slate-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-3-3v6m2 4H7a2 2 0 01-2-2V7a2 2 0 012-2h3l2 2h4a2 2 0 012 2v7a2 2 0 01-2 2z" />
                        </svg>
                        You do not have any leave requests.
                    </div>
                @else
                    <ul class="space-y-2">
                        @foreach ($leaveRequests as $lr)
                            @php $st = strtolower(trim($lr->final_status ?? ($lr->status ?? ''))); @endphp
                            <li
                                class="bg-slate-50 p-3 rounded-xl shadow-sm flex items-center justify-between leave-item">
                                <div>
                                    <div class="font-semibold text-slate-900">{{ ucfirst($lr->type ?? 'Annual') }} —
                                        @include('components.date-range', [
                                            'start' => $lr->start_date,
                                            'end' => $lr->end_date,
                                            'legacy' => $lr->date ?? null,
                                        ])</div>
                                    <div class="text-sm text-slate-500 mt-1">
                                        {{ \Illuminate\Support\Str::limit($lr->reason ?? '', 120) }}</div>
                                </div>
                                <div class="ml-4">@include('components.status-badge', [
                                    'status' => $lr->final_status ?? $lr->status,
                                    'size' => 'text-sm',
                                ])</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    <div class="leave-panel">
        <h3 class="font-semibold mb-2">Brief information</h3>
        <p class="text-sm text-gray-500">If you need assistance with your application or have questions regarding
            leave, please contact HR or your manager.</p>
    </div>
</section>
</div>

<script>
    // live clock
    (function() {
        function pad(n) {
            return n < 10 ? '0' + n : n
        }

        function updateClock() {
            var now = new Date();
            var h = pad(now.getHours());
            var m = pad(now.getMinutes());
            var s = pad(now.getSeconds());
            var el = document.getElementById('dashboard-clock');
            var d = document.getElementById('dashboard-date');
            if (el) el.textContent = h + ':' + m + ':' + s;
            if (d) d.textContent = now.toLocaleDateString(undefined, {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            })
        }
        updateClock();
        setInterval(updateClock, 1000)
    })();
</script>
