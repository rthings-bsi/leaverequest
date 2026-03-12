@extends('layouts.app')

@section('title', 'Dashboard - Time Request')

@section('content')

    <main class="max-w-7xl mx-auto p-6">

        @php
            $isMgmt = false;
            try {
                $currentUser = auth()->user();

                // Collect role names (safe when getRoleNames() is not present)
                $roleNames = collect([]);
                if ($currentUser && method_exists($currentUser, 'getRoleNames')) {
                    $roleNames = collect($currentUser->getRoleNames())->map(function ($r) {
                        return strtolower((string) $r);
                    });
                }

                $isMgmt =
                    $roleNames->contains(function ($r) {
                        return str_contains($r, 'manager') ||
                            str_contains($r, 'supervisor') ||
                            str_contains($r, 'hod') ||
                            str_contains($r, 'head') ||
                            str_contains($r, 'kepala');
                    }) ||
                    ($currentUser && method_exists($currentUser, 'isManager') && $currentUser->isManager()) ||
                    ($currentUser && method_exists($currentUser, 'isSupervisor') && $currentUser->isSupervisor()) ||
                    ($currentUser && method_exists($currentUser, 'isHod') && $currentUser->isHod());
            } catch (\Throwable $e) {
                $isMgmt = false;
            }
        @endphp

        <style>
            /* Sharper, more vivid manager dashboard styles */
            .dashboard-header-gradient {
                background: linear-gradient(135deg, rgba(79, 70, 229, 0.16), rgba(14, 165, 233, 0.08));
                border: 1px solid rgba(79, 70, 229, 0.12);
                backdrop-filter: blur(6px) saturate(140%);
                box-shadow: 0 10px 30px rgba(79, 70, 229, 0.04) inset;
            }

            .stat-bar .h-2 {
                background: rgba(15, 23, 42, 0.06) !important;
            }

            .stat-bar-fill {
                height: 100%;
                transition: width 360ms cubic-bezier(.22, .9, .25, 1), box-shadow 160ms ease;
                background: linear-gradient(90deg, rgba(var(--accent-rgb), 1), rgba(var(--accent-rgb), 0.8));
                border-radius: 999px;
                filter: saturate(1.25);
                box-shadow: 0 14px 40px rgba(var(--accent-rgb), 0.12);
            }

            .stat-card {
                border-radius: 16px;
                padding: 18px;
                transition: transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease;
                background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(250, 250, 255, 0.9));
                box-shadow: 0 10px 40px rgba(2, 6, 23, 0.06);
                border: 1px solid rgba(15, 23, 42, 0.04);
            }

            .stat-card:hover {
                transform: translateY(-8px) scale(1.02);
                box-shadow: 0 28px 80px rgba(2, 6, 23, 0.14);
            }

            .stat-card[data-accent] {
                border-left: 6px solid rgba(var(--accent-rgb), 0.95);
                box-shadow: 0 12px 40px rgba(var(--accent-rgb), 0.06);
            }

            .stat-value {
                color: rgb(17 24 39);
            }

            .icon-tile {
                width: 48px;
                height: 48px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, rgba(255, 255, 255, 0.75), rgba(255, 255, 255, 0.45));
                box-shadow: 0 8px 24px rgba(2, 6, 23, 0.06);
            }

            .stat-bar-label {
                font-weight: 700;
                color: rgba(15, 23, 42, 0.55);
            }

            .stat-done {
                transform: scale(1.02);
            }

            .dashboard-header-content h1 {
                letter-spacing: -0.02em;
            }

            .stat-card:hover {
                transform: translateY(-6px) scale(1.01);
                box-shadow: 0 18px 50px rgba(2, 6, 23, 0.12);
            }

            .stat-value {
                color: rgba(2, 6, 23, 0.95);
            }

            .icon-tile {
                width: 44px;
                height: 44px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, rgba(255, 255, 255, 0.6), rgba(255, 255, 255, 0.35));
                box-shadow: 0 6px 18px rgba(2, 6, 23, 0.06);
            }

            /* Color svg icons to match the card accent */
            .stat-card[data-accent] .icon-tile svg {
                fill: rgb(var(--accent-rgb));
                color: rgb(var(--accent-rgb));
            }

            /* Brighter accent fallback when --accent-rgb present */
            .stat-card[data-accent] .stat-bar-fill {
                box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
            }

            .stat-bar-label {
                font-weight: 700;
                color: rgba(0, 0, 0, 0.5);
            }

            /* Neat small header badge */
            .dashboard-header-content h1 {
                letter-spacing: -0.02em;
            }
        </style>
        <section class="mb-6">
            <div class="p-6 rounded-xl shadow-md ring-1 ring-blue-100 dashboard-header-gradient"
                @if (auth()->check() &&
                        ((method_exists(auth()->user(), 'isHrd') && auth()->user()->isHrd()) ||
                            (method_exists(auth()->user(), 'isManager') && auth()->user()->isManager()))) style="background: linear-gradient(90deg, #83A4D4 0%, #B6FBFF 100%);" @endif>
                <div class="dashboard-header-overlay" aria-hidden="true"></div>
                <div class="dashboard-header-content">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1 md:pr-6">
                            <h1 class="text-3xl md:text-4xl font-bold text-slate-900">Dashboard - Time Request</h1>
                            <p class="text-sm text-slate-600">Submit leave requests and view employee request history</p>
                        </div>
                        <div class="flex-shrink-0 text-left md:text-right">
                            <div id="clock" class="text-2xl md:text-4xl font-medium text-slate-900">--:--:--</div>
                            <div id="date" class="text-sm text-slate-500">--</div>
                            @php
                                $paperSaved = $totalRequests ?? 0; // 1 request = 1 sheet
                                $treesSaved = $paperSaved / 100; // 100 sheets = 1 small tree
                            @endphp
                            <div id="go-green"
                                class="mt-3 inline-flex items-center gap-3 text-sm text-green-700 bg-green-50 border border-green-100 px-3 py-2 rounded"
                                data-paper="{{ $paperSaved }}" data-trees="{{ $treesSaved }}">
                                <span class="text-lg">🌱</span>
                                <div>
                                    <div class="font-semibold">Go Green</div>
                                    <div id="go-green-stats">Saved <strong>{{ $paperSaved }}</strong> sheets •
                                        ~<strong>{{ number_format($treesSaved, 2) }}</strong> small trees</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @php
            // Safe defaults so the manager dashboard can be rendered even when the
            // controller does not provide every variable (prevents Undefined variable errors).
            $users = $users ?? collect();
            $leaveRequests = $leaveRequests ?? collect();
            $totalRequests = $totalRequests ?? ($leaveRequests ? $leaveRequests->count() : 0);
            $todaysRequests = $todaysRequests ?? 0;
            $pending = $pending ?? ($leaveRequests ? $leaveRequests->where('status', 'pending')->count() : 0);
        @endphp

        @if ($isMgmt && empty(auth()->user()->department))
            <section class="mb-6">
                <div class="max-w-4xl mx-auto p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-800 rounded">
                    <div class="font-semibold">You do not yet have a department</div>
                    <div class="text-sm">Fill in your department in <a href="{{ route('profile.edit') }}"
                            class="text-blue-600 underline">Edit Profile</a> so you can view and approve employee leave
                        requests in your department.</div>
                </div>
            </section>
        @endif

        <!-- Stats grid -->
        @php
            $toInt = function ($v) {
                if (is_int($v)) {
                    return $v;
                }
                if (is_numeric($v)) {
                    return (int) $v;
                }
                if (is_countable($v)) {
                    return count($v);
                }
                return 0;
            };

            $total = $toInt($totalRequests ?? 0);
            $today = $toInt($todaysRequests ?? 0);
            $pendingV = $toInt($pending ?? 0);
            $acceptedV = $toInt($accepted ?? 0);
            $rejectedV = $toInt($rejected ?? 0);
            $employeesV = $toInt($employeesCount ?? 0);
            $divisionsV = $toInt($divisionsCount ?? 0);
            $max = max([$total, $today, $pendingV, $acceptedV, $rejectedV, $employeesV, $divisionsV]);
        @endphp

        {{-- Show a small label when a manager is viewing their own leaves to avoid confusion about scope --}}
        @if ($isMgmt)
            <div class="mb-3 max-w-7xl mx-auto px-2">
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-sm font-medium">
                    Your leave and your employees in your department only
                </span>
            </div>
        @endif

        {{-- Manager: show only the Rejected stat; Non-manager: full stats grid --}}
        @if ($isMgmt)
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
                {{-- Today's Requests --}}
                <div data-accent
                    style="--accent-rgb: 0,186,212; background: linear-gradient(135deg, rgba(0,186,212,0.14), rgba(99,102,241,0.03));"
                    class="rounded-lg p-4 shadow-lg stat-card" data-stat="today">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 icon-tile flex items-center justify-center text-cyan-600">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M7 2a1 1 0 100 2h1v1a1 1 0 102 0V4h4v1a1 1 0 102 0V4h1a1 1 0 100-2H7zM4 9a1 1 0 011-1h14a1 1 0 011 1v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            </svg>
                        </div>
                        <div class="text-sm text-slate-600 font-extrabold">Today's Requests</div>
                    </div>
                    @php
                        $val = $today ?? 0;
                        $pct = $max ? round(($val / $max) * 100) : 0;
                    @endphp
                    <div class="mt-4 stat-bar">
                        <div class="flex-1">
                            <div class="h-2 bg-white/60 rounded-full overflow-hidden" role="progressbar"
                                aria-valuenow="{{ $val }}" aria-valuemax="{{ $max }}"
                                aria-valuetext="{{ $pct }}%">
                                <div class="stat-bar-fill" data-width="{{ $pct }}" style="width: 0%;"></div>
                            </div>
                        </div>
                        <div class="stat-bar-label">{{ $pct }}%</div>
                    </div>
                    <div class="stat-value text-2xl md:text-3xl font-extrabold mt-3 text-slate-900"><span
                            class="stat-value-anim" data-target="{{ $val }}">0</span></div>
                    <div class="text-xs text-slate-500 mt-1">{{ now()->format('d M Y') }}</div>
                </div>

                {{-- Approved --}}
                <div data-accent
                    style="--accent-rgb: 16,185,129; background: linear-gradient(135deg, rgba(16,185,129,0.12), rgba(6,182,212,0.03));"
                    class="rounded-lg p-4 shadow-lg stat-card" data-stat="accepted">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 icon-tile flex items-center justify-center text-emerald-600">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M20.285 6.709a1 1 0 00-1.414-1.418L9 15.16l-3.871-3.87a1 1 0 00-1.415 1.414l4.578 4.578a1 1 0 001.414 0L20.285 6.709z" />
                            </svg>
                        </div>
                        <div class="text-sm text-slate-600 font-extrabold">Approved</div>
                    </div>
                    @php
                        $val = $acceptedV ?? 0;
                        $pct = $max ? round(($val / $max) * 100) : 0;
                    @endphp
                    <div class="mt-4 stat-bar">
                        <div class="flex-1">
                            <div class="h-2 bg-white/60 rounded-full overflow-hidden" role="progressbar"
                                aria-valuenow="{{ $val }}" aria-valuemax="{{ $max }}"
                                aria-valuetext="{{ $pct }}%">
                                <div class="stat-bar-fill" data-width="{{ $pct }}" style="width: 0%;"></div>
                            </div>
                        </div>
                        <div class="stat-bar-label">{{ $pct }}%</div>
                    </div>
                    <div class="stat-value text-2xl md:text-3xl font-extrabold mt-3 text-slate-900"><span
                            class="stat-value-anim" data-target="{{ $val }}">0</span></div>
                    <div class="text-xs text-slate-500 mt-1">Approved Leave</div>
                </div>

                {{-- Pending --}}
                <div data-accent
                    style="--accent-rgb: 250,204,21; background: linear-gradient(135deg, rgba(250,204,21,0.14), rgba(255,249,196,0.04));"
                    class="rounded-lg p-4 shadow-lg stat-card" data-stat="pending">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 icon-tile flex items-center justify-center text-rose-500">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 8v4l3 3a1 1 0 11-1.414 1.414L11 13.414V8a1 1 0 112 0z" />
                            </svg>
                        </div>
                        <div class="text-sm text-slate-600 font-extrabold">Pending</div>
                    </div>
                    @php
                        $val = $pendingV ?? 0;
                        $pct = $max ? round(($val / $max) * 100) : 0;
                    @endphp
                    <div class="mt-4 stat-bar">
                        <div class="flex-1">
                            <div class="h-2 bg-white/60 rounded-full overflow-hidden" role="progressbar"
                                aria-valuenow="{{ $val }}" aria-valuemax="{{ $max }}"
                                aria-valuetext="{{ $pct }}%">
                                <div class="stat-bar-fill" data-width="{{ $pct }}" style="width: 0%;"></div>
                            </div>
                        </div>
                        <div class="stat-bar-label">{{ $pct }}%</div>
                    </div>
                    <div class="stat-value text-2xl md:text-3xl font-extrabold mt-3 text-slate-900"><span
                            class="stat-value-anim" data-target="{{ $val }}">0</span></div>
                    <div class="text-xs text-slate-500 mt-1">Waiting Processing</div>
                </div>

                {{-- Rejected --}}
                <div data-accent
                    style="--accent-rgb: 244,63,94; background: linear-gradient(135deg, rgba(244,63,94,0.16), rgba(255,235,238,0.04));"
                    class="rounded-lg p-4 shadow-lg stat-card" data-stat="rejected">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 icon-tile flex items-center justify-center text-rose-500">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M18.364 5.636a1 1 0 00-1.414 0L12 10.586 7.05 5.636A1 1 0 105.636 7.05L10.586 12l-4.95 4.95a1 1 0 101.414 1.414L12 13.414l4.95 4.95a1 1 0 001.414-1.414L13.414 12l4.95-4.95a1 1 0 000-1.414z" />
                            </svg>
                        </div>
                        <div class="text-sm text-slate-600 font-extrabold">Rejected</div>
                    </div>
                    @php
                        $val = $rejectedV ?? 0;
                        $pct = $max ? round(($val / $max) * 100) : 0;
                    @endphp
                    <div class="mt-4 stat-bar">
                        <div class="flex-1">
                            <div class="h-2 bg-white/60 rounded-full overflow-hidden" role="progressbar"
                                aria-valuenow="{{ $val }}" aria-valuemax="{{ $max }}"
                                aria-valuetext="{{ $pct }}%">
                                <div class="stat-bar-fill" data-width="{{ $pct }}" style="width: 0%;"></div>
                            </div>
                        </div>
                        <div class="stat-bar-label">{{ $pct }}%</div>
                    </div>
                    <div class="stat-value text-2xl md:text-3xl font-extrabold mt-3 text-slate-900"><span
                            class="stat-value-anim" data-target="{{ $val }}">0</span></div>
                    <div class="text-xs text-slate-500 mt-1">Rejected Leave</div>
                </div>
            </section>
        @else
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
                @unless ($isMgmt)
                    <div style="--accent-rgb: 79,70,229"
                        class="rounded-lg p-4 shadow-lg stat-card cursor-pointer transition-all duration-300 ease-out transform hover:-translate-y-1 hover:scale-[1.01] bg-gradient-to-r from-indigo-50 to-white border border-white/60"
                        data-stat="total">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 icon-tile flex items-center justify-center text-indigo-600">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M12 2a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0V8H8a1 1 0 110-2h3V3a1 1 0 011-1z" />
                                </svg>
                            </div>
                            <div class="text-sm text-slate-600 font-extrabold">Total Requests</div>
                        </div>
                        @php
                            $val = $total;
                            $pct = $max ? round(($val / $max) * 100) : 0;
                        @endphp
                        <div class="mt-4 stat-bar">
                            <div class="flex-1">
                                <div class="h-2 bg-white/60 rounded-full overflow-hidden" role="progressbar"
                                    aria-valuenow="{{ $val }}" aria-valuemax="{{ $max }}"
                                    aria-valuetext="{{ $pct }}%">
                                    <div class="stat-bar-fill" data-width="{{ $pct }}"
                                        style="width: 0%; background: rgba(var(--accent-rgb),0.9);"></div>
                                </div>
                            </div>
                            <div class="stat-bar-label text-xs">{{ $pct }}%</div>
                        </div>
                        <div class="stat-value text-2xl md:text-3xl font-extrabold mt-3 text-slate-900">{{ $val }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">Total leave requests</div>
                    </div>
                @endunless

                <div style="--accent-rgb: 6,182,212"
                    class="rounded-lg p-4 shadow-lg stat-card cursor-pointer transition-all duration-300 ease-out transform hover:-translate-y-1 hover:scale-[1.01] bg-gradient-to-r from-cyan-50 to-white ring-1 ring-green-50 border border-white/60"
                    data-stat="today">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 icon-tile flex items-center justify-center text-cyan-600">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M7 2a1 1 0 100 2h1v1a1 1 0 102 0V4h4v1a1 1 0 102 0V4h1a1 1 0 100-2H7zM4 9a1 1 0 011-1h14a1 1 0 011 1v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            </svg>
                        </div>
                        <div class="text-sm text-slate-600 font-extrabold">Today's Requests</div>
                    </div>
                    @php
                        $val = $today;
                        $pct = $max ? round(($val / $max) * 100) : 0;
                    @endphp
                    <div class="mt-4 stat-bar">
                        <div class="flex-1">
                            <div class="h-2 bg-white/60 rounded-full overflow-hidden" role="progressbar"
                                aria-valuenow="{{ $val }}" aria-valuemax="{{ $max }}"
                                aria-valuetext="{{ $pct }}%">
                                <div class="stat-bar-fill" data-width="{{ $pct }}"
                                    style="width: 0%; background: rgba(var(--accent-rgb),0.9);"></div>
                            </div>
                        </div>
                        <div class="stat-bar-label">{{ $pct }}%</div>
                    </div>
                    <div class="stat-value text-2xl md:text-3xl font-extrabold mt-3 text-slate-900">{{ $val }}
                    </div>
                    <div class="text-xs text-slate-500 mt-1">{{ now()->format('d M Y') }}</div>
                </div>

                <div style="--accent-rgb: 244,63,94"
                    class="rounded-lg p-4 shadow-lg stat-card cursor-pointer transition-all duration-300 ease-out transform hover:-translate-y-1 hover:scale-[1.01] bg-gradient-to-r from-rose-50 to-white border border-white/60"
                    data-stat="pending">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 icon-tile flex items-center justify-center text-rose-500">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 8v4l3 3a1 1 0 11-1.414 1.414L11 13.414V8a1 1 0 112 0z" />
                            </svg>
                        </div>
                        <div class="text-sm text-slate-600 font-extrabold">Pending</div>
                    </div>
                    @php
                        $val = $pendingV;
                        $pct = $max ? round(($val / $max) * 100) : 0;
                    @endphp
                    <div class="mt-4 stat-bar">
                        <div class="flex-1">
                            <div class="h-2 bg-white/60 rounded-full overflow-hidden" role="progressbar"
                                aria-valuenow="{{ $val }}" aria-valuemax="{{ $max }}"
                                aria-valuetext="{{ $pct }}%">
                                <div class="stat-bar-fill" data-width="{{ $pct }}"
                                    style="width: 0%; background: rgba(var(--accent-rgb),0.9);"></div>
                            </div>
                        </div>
                        <div class="stat-bar-label">{{ $pct }}%</div>
                    </div>
                    <div class="stat-value text-2xl md:text-3xl font-extrabold mt-3 text-slate-900">{{ $val }}
                    </div>
                    <div class="text-xs text-slate-500 mt-1">Waiting Processing</div>
                </div>

                <div style="--accent-rgb: 16,185,129"
                    class="rounded-lg p-4 shadow-lg stat-card cursor-pointer transition-all duration-300 ease-out transform hover:-translate-y-1 hover:scale-[1.01] bg-gradient-to-r from-green-50 to-white border border-white/60"
                    data-stat="accepted">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 icon-tile flex items-center justify-center text-emerald-600">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M20.285 6.709a1 1 0 00-1.414-1.418L9 15.16l-3.871-3.87a1 1 0 00-1.415 1.414l4.578 4.578a1 1 0 001.414 0L20.285 6.709z" />
                            </svg>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="text-sm text-slate-600 font-extrabold">Approved</div <button
                                class="ml-1 text-slate-400" aria-label="Approved equals approved"
                                title="Approved = approved"
                                style="background:transparent;border:0;padding:0;font-size:12px;line-height:1;">ℹ️</button>
                        </div>
                    </div>
                    @php
                        $val = $acceptedV;
                        $pct = $max ? round(($val / $max) * 100) : 0;
                    @endphp
                    <div class="mt-4 stat-bar">
                        <div class="flex-1">
                            <div class="h-2 bg-white/60 rounded-full overflow-hidden" role="progressbar"
                                aria-valuenow="{{ $val }}" aria-valuemax="{{ $max }}"
                                aria-valuetext="{{ $pct }}%">
                                <div class="stat-bar-fill" data-width="{{ $pct }}"
                                    style="width: 0%; background: rgba(var(--accent-rgb),0.9);"></div>
                            </div>
                        </div>
                        <div class="stat-bar-label">{{ $pct }}%</div>
                    </div>
                    <div class="stat-value text-2xl md:text-3xl font-extrabold mt-3 text-slate-900">{{ $val }}
                    </div>
                    <div class="text-xs text-slate-500 mt-1">Approved Leave</div>
                </div>

                <div style="--accent-rgb: 244,63,94"
                    class="rounded-lg p-4 shadow-lg stat-card cursor-pointer transition-all duration-300 ease-out transform hover:-translate-y-1 hover:scale-[1.01] bg-gradient-to-r from-rose-50 to-white border border-white/60"
                    data-stat="rejected">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 icon-tile flex items-center justify-center text-rose-500">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M18.364 5.636a1 1 0 00-1.414 0L12 10.586 7.05 5.636A1 1 0 105.636 7.05L10.586 12l-4.95 4.95a1 1 0 101.414 1.414L12 13.414l4.95 4.95a1 1 0 001.414-1.414L13.414 12l4.95-4.95a1 1 0 000-1.414z" />
                            </svg>
                        </div>
                        <div class="text-sm text-slate-600 font-extrabold">Rejected</div>
                    </div>
                    @php
                        $val = $rejectedV;
                        $pct = $max ? round(($val / $max) * 100) : 0;
                    @endphp
                    <div class="mt-4 stat-bar">
                        <div class="flex-1">
                            <div class="h-2 bg-white/60 rounded-full overflow-hidden" role="progressbar"
                                aria-valuenow="{{ $val }}" aria-valuemax="{{ $max }}"
                                aria-valuetext="{{ $pct }}%">
                                <div class="stat-bar-fill" data-width="{{ $pct }}"
                                    style="width: 0%; background: rgba(var(--accent-rgb),0.9);"></div>
                            </div>
                        </div>
                        <div class="stat-bar-label">{{ $pct }}%</div>
                    </div>
                    <div class="stat-value text-2xl md:text-3xl font-extrabold mt-3 text-slate-900">{{ $val }}
                    </div>
                    <div class="text-xs text-slate-500 mt-1">Rejected Leave</div>
                </div>

                @unless ($isMgmt)
                    <div style="--accent-rgb: 14,165,233"
                        class="rounded-lg p-4 shadow-lg stat-card cursor-pointer transition-all duration-300 ease-out transform hover:-translate-y-1 hover:scale-[1.01] bg-gradient-to-r from-sky-50 to-white border border-white/60"
                        data-stat="employees">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 icon-tile flex items-center justify-center text-sky-600">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M17 20h5v-2a4 4 0 00-3-3.87A6 6 0 0012 12a6 6 0 00-7 6v2h5" />
                                </svg>
                            </div>
                            <div class="text-sm text-slate-600 font-extrabold">Employees</div>
                        </div>
                        @php
                            $val = $employeesV;
                            $pct = $max ? round(($val / $max) * 100) : 0;
                        @endphp
                        <div class="mt-4 stat-bar">
                            <div class="flex-1">
                                <div class="h-2 bg-white/60 rounded-full overflow-hidden" role="progressbar"
                                    aria-valuenow="{{ $val }}" aria-valuemax="{{ $max }}"
                                    aria-valuetext="{{ $pct }}%">
                                    <div class="stat-bar-fill" data-width="{{ $pct }}"
                                        style="width: 0%; background: rgba(var(--accent-rgb),0.9);"></div>
                                </div>
                            </div>
                            <div class="stat-bar-label">{{ $pct }}%</div>
                        </div>
                        <div class="stat-value text-2xl md:text-3xl font-extrabold mt-3 text-slate-900">{{ $val }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">Total Employees Requesting Leave</div>
                    </div>
                @endunless

                @unless ($isMgmt)
                    <div style="--accent-rgb: 139,92,246"
                        class="rounded-lg p-4 shadow-lg stat-card cursor-pointer transition-all duration-300 ease-out transform hover:-translate-y-1 hover:scale-[1.01] bg-gradient-to-r from-purple-50 to-white border border-white/60"
                        data-stat="divisions">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 icon-tile flex items-center justify-center text-purple-600">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 7h18v2H3V7zm0 5h18v2H3v-2zM3 17h18v2H3v-2z" />
                                </svg>
                            </div>
                            <div class="text-sm text-slate-600 font-extrabold">Department</div>
                        </div>
                        @php
                            // department stat should use divisions count computed earlier
                            $val = $divisionsV ?? 0;
                            $pct = $max ? round(($val / $max) * 100) : 0;
                        @endphp
                        <div class="mt-4 stat-bar">
                            <div class="flex-1">
                                <div class="h-2 bg-white/60 rounded-full overflow-hidden" role="progressbar"
                                    aria-valuenow="{{ $val }}" aria-valuemax="{{ $max }}"
                                    aria-valuetext="{{ $pct }}%">
                                    <div class="stat-bar-fill" data-width="{{ $pct }}"
                                        style="width: 0%; background: rgba(var(--accent-rgb),0.9);"></div>
                                </div>
                            </div>
                            <div class="stat-bar-label">{{ $pct }}%</div>
                        </div>
                        <div class="stat-value text-2xl md:text-3xl font-extrabold mt-3 text-slate-900">{{ $val }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">Department Requesting Leave</div>
                    </div>
                @endunless
            </section>
        @endif

        <!-- Employees and Activity -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @unless ($isMgmt)
                <div class="lg:col-span-2">
                    @include('partials.dashboard-cards')
                </div>
            @endunless

            {{-- All Employees panel removed per manager view request --}}

            @if ($isMgmt)
                <div class="rounded-lg p-6 shadow-sm bg-gradient-to-r from-cyan-50 to-white border-l-4 border-cyan-400">
                    <h2 class="font-semibold mb-4 flex items-center gap-2 text-cyan-700"><svg class="w-5 h-5"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m2 0a8 8 0 11-16 0 8 8 0 0116 0z"></path>
                        </svg> Leave Application Activity</h2>

                    @if (!empty(auth()->user()->department))
                        <p class="text-sm text-gray-600 mb-4">Displaying leave requests for your department:
                            <strong>{{ auth()->user()->department }}</strong>
                        </p>
                        <p class="text-sm">Manage applications on the <a href="{{ route('approvals.index') }}"
                                class="text-cyan-600 underline">Approval Page</a>.</p>

                        {{-- Incoming pending requests list (department-scoped) removed per request --}}

                        {{-- History (optional) --}}
                        @if (isset($history) && $history->isNotEmpty())
                            <div class="mt-6">
                                <h3 class="font-semibold mb-2">Request History</h3>
                                <ul class="space-y-3">
                                    @foreach ($history as $h)
                                        <li class="p-3 border rounded flex justify-between items-center">
                                            <div class="min-w-0">
                                                @php
                                                    try {
                                                        $hstart = $h->start_date
                                                            ? \Illuminate\Support\Carbon::parse($h->start_date)->format(
                                                                'd M Y',
                                                            )
                                                            : ($h->date
                                                                ? \Illuminate\Support\Carbon::parse($h->date)->format(
                                                                    'd M Y',
                                                                )
                                                                : '—');
                                                    } catch (\Throwable $e) {
                                                        $hstart = $h->start_date ?? ($h->date ?? '—');
                                                    }
                                                    try {
                                                        $hend = $h->end_date
                                                            ? \Illuminate\Support\Carbon::parse($h->end_date)->format(
                                                                'd M Y',
                                                            )
                                                            : ($h->date
                                                                ? \Illuminate\Support\Carbon::parse($h->date)->format(
                                                                    'd M Y',
                                                                )
                                                                : '—');
                                                    } catch (\Throwable $e) {
                                                        $hend = $h->end_date ?? ($h->date ?? '—');
                                                    }
                                                @endphp
                                                <div class="font-semibold flex items-center justify-between gap-3">
                                                    <div class="truncate min-w-0">{{ optional($h->user)->name ?? '—' }} —
                                                        {{ ucfirst($h->final_status ?? ($h->status ?? '')) }}</div>
                                                    <div class="text-xs text-gray-400 whitespace-nowrap ms-3">
                                                        @include('components.date-range', [
                                                            'start' => $hstart,
                                                            'end' => $hend,
                                                        ])</div>
                                                </div>
                                                {{-- removed date/fallback display as requested --}}
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @else
                        <p class="text-sm text-yellow-700 mb-4">You do not have a department set.
                            Please <a href="{{ route('profile.edit') }}" class="text-cyan-600 underline">edit your
                                profile</a>
                            to add a department in order to view subordinate applications.</p>
                    @endif
                </div>
            @else
                <div class="rounded-lg p-6 shadow-sm bg-gradient-to-r from-cyan-50 to-white border-l-4 border-cyan-400">
                    <h2 class="font-semibold mb-4 flex items-center gap-2 text-cyan-700"><svg class="w-5 h-5"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m2 0a8 8 0 11-16 0 8 8 0 0116 0z"></path>
                        </svg> Leave Application Activity</h2>
                    <p class="text-sm text-gray-600 mb-4">Total applications: {{ $totalRequests }}</p>

                    <div class="space-y-4">
                        @forelse($leaveRequests as $lr)
                            <div
                                class="p-4 border rounded-lg flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                                <div class="min-w-0">
                                    <div class="font-semibold truncate">{{ optional($lr->user)->name ?? 'Unknown' }} —
                                        {{ ucfirst($lr->type ?? 'leave') }}</div>
                                    <div class="text-xs text-gray-400 truncate">
                                        {{-- Prefer end_date if present; fallback to legacy `date` column --}}
                                        @php
                                            $start =
                                                optional($lr->start_date)->format('d M Y') ??
                                                ($lr->start_date ??
                                                    ($lr->date ? optional($lr->date)->format('d M Y') : null));
                                            $end =
                                                optional($lr->end_date)->format('d M Y') ??
                                                ($lr->end_date ??
                                                    ($lr->date ? optional($lr->date)->format('d M Y') : null));
                                        @endphp
                                        @include('components.date-range', [
                                            'start' => $start,
                                            'end' => $end,
                                        ])
                                    </div>
                                    @if ($lr->notes)
                                        <div class="text-xs text-gray-400 mt-2 truncate">{{ $lr->notes }}</div>
                                    @endif
                                </div>
                                <div class="flex-shrink-0">
                                    <div>
                                        @include('components.status-badge', [
                                            'status' => $lr->final_status ?? $lr->status,
                                            'size' => 'text-sm',
                                        ])
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-gray-500">No leave requests found.</div>
                        @endforelse
                    </div>
                </div>
            @endif
        </section>


        <script>
            function startClock() {
                const clockEl = document.getElementById('clock');
                const dateEl = document.getElementById('date');

                function pad(n) {
                    return n < 10 ? '0' + n : n;
                }

                function update() {
                    const now = new Date();
                    const hh = pad(now.getHours());
                    const mm = pad(now.getMinutes());
                    const ss = pad(now.getSeconds());
                    if (clockEl) clockEl.textContent = `${hh}:${mm}:${ss}`;

                    const day = pad(now.getDate());
                    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    const month = monthNames[now.getMonth()];
                    const year = now.getFullYear();
                    if (dateEl) dateEl.textContent = `${day} ${month} ${year}`;
                }

                update();
                setInterval(update, 1000);
            }
            startClock();
            // Poll the server for today's data and update the All Employees and Leave Activity lists
            async function fetchTodayData() {
                try {
                    const res = await fetch("/dashboard/today", {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) return;
                    const data = await res.json();

                    // Update total count label
                    const totalLabel = document.querySelector('p.text-sm.text-gray-500.mb-4');
                    if (totalLabel) {
                        totalLabel.textContent = `Total of applications: ${data.totalRequests ?? 0}`;
                    }

                    // Update All Employees list - show only users with leave today
                    const usersList = document.querySelector('div[data-stat="department"] ul');
                    if (usersList) {
                        if ((data.users || []).length === 0) {
                            usersList.innerHTML = '<li class="py-4 text-sm text-gray-500">No users found for today.</li>';
                        } else {
                            usersList.innerHTML = (data.users || []).map(u =>
                                `\n<li class="flex items-center justify-between py-3 md:py-4">\n  <div class="flex items-center gap-3 md:gap-4 min-w-0">\n    <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-gradient-to-r from-green-300 to-blue-300 flex items-center justify-center text-white font-semibold flex-shrink-0">${(u.name||'')[0] || ''}${(u.name||'')[1] || ''}</div>\n    <div class="min-w-0">\n      <div class="font-semibold truncate">${u.name}</div>\n      <div class="text-xs text-gray-500 truncate">${u.email}</div>\n    </div>\n  </div>\n  <div class="text-right ml-4 md:ml-6">\n    <div class="font-semibold">${u.leaves_left ?? '—'}</div>\n    <div class="text-xs text-gray-400">Leaves left</div>\n  </div>\n</li>`
                            ).join('\n');
                        }
                    }

                    // Update Leave Application Activity
                    const activityContainer = document.querySelector('div.space-y-4');
                    if (activityContainer) {
                        if ((data.leaveRequests || []).length === 0) {
                            activityContainer.innerHTML =
                                '<div class="text-sm text-gray-500">No leave requests found.</div>';
                        } else {
                            activityContainer.innerHTML = (data.leaveRequests || []).map(lr =>
                                `\n<div class="p-4 border rounded-lg flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">\n  <div class="min-w-0">\n    <div class="font-semibold truncate">${lr.user_name} — ${lr.type.charAt(0).toUpperCase()+lr.type.slice(1)}</div>\n    <div class="text-xs text-gray-400 truncate">${lr.start_date ?? ''} to ${lr.end_date ?? ''}</div>\n    ${lr.notes ? `<div class="text-xs text-gray-400 mt-2 truncate">${lr.notes}</div>` : ''}\n  </div>\n  <div class="flex-shrink-0">\n    <div class="px-3 py-1 rounded-full bg-rose-100 text-rose-700 text-sm">${(lr.status||'pending').charAt(0).toUpperCase()+ (lr.status||'pending').slice(1)}</div>\n  </div>\n</div>`
                            ).join('\n');
                        }
                    }
                } catch (e) {
                    console.error('Failed to fetch today data', e);
                }
            }

            // Initial fetch and interval for today's data
            fetchTodayData();
            setInterval(fetchTodayData, 15000); // every 15 seconds

            // Poll the stats endpoint and update stat cards (numbers + progress bars)
            async function fetchStats() {
                try {
                    const res = await fetch('/dashboard/stats', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) return;
                    const data = await res.json();

                    // Mapping between card data-stat and API fields
                    const mapping = {
                        total: 'total',
                        today: 'today',
                        pending: 'pending',
                        accepted: 'accepted',
                        rejected: 'rejected',
                        employees: 'employees',
                        divisions: 'departments'
                    };

                    // Determine max for percent calculation (avoid division by zero)
                    const values = Object.values(mapping).map(k => Number(data[k] || 0));
                    const max = values.length ? Math.max(...values, 1) : 1;

                    // Update each stat card
                    document.querySelectorAll('.stat-card[data-stat]').forEach(card => {
                        const key = card.getAttribute('data-stat');
                        const apiKey = mapping[key];
                        if (!apiKey) return;
                        const val = Number(data[apiKey] || 0);

                        // Update numeric value element (.stat-value)
                        const valEl = card.querySelector('.stat-value');
                        if (valEl) valEl.textContent = val;

                        // Update percent label and progress bar
                        const pct = max ? Math.round((val / max) * 100) : 0;
                        const pctLabel = card.querySelector('.stat-bar-label');
                        if (pctLabel) pctLabel.textContent = `${pct}%`;

                        const barFill = card.querySelector('.stat-bar-fill');
                        if (barFill) {
                            // animate width change
                            const target = `${pct}%`;
                            // set data-width attr for compatibility
                            barFill.setAttribute('data-width', pct);
                            // color the label to match accent color
                            const accent = getComputedStyle(card).getPropertyValue('--accent-rgb') || '';
                            if (pctLabel && accent) {
                                pctLabel.style.color = `rgb(${accent.trim()})`;
                            }
                            // if 100% make the bar fully saturated
                            requestAnimationFrame(() => {
                                barFill.style.width = target;
                                if (pct >= 100) {
                                    barFill.style.background =
                                        `linear-gradient(90deg, rgb(${accent.trim()}), rgba(${accent.trim()},0.9))`;
                                } else {
                                    barFill.style.background = '';
                                }
                            });
                        }
                    });

                    // Update Go Green tracker if present
                    const goGreen = document.getElementById('go-green');
                    if (goGreen) {
                        const paper = Number(data.total || data.totalRequests || 0);
                        const trees = paper / 100;
                        goGreen.setAttribute('data-paper', paper);
                        goGreen.setAttribute('data-trees', trees);
                        const statsEl = document.getElementById('go-green-stats');
                        if (statsEl) {
                            statsEl.innerHTML =
                                `Saved <strong>${paper}</strong> sheets • ~<strong>${trees.toFixed(2)}</strong> small trees`;
                        }
                    }
                } catch (e) {
                    console.error('Failed to fetch stats', e);
                }
            }

            // Initial fetch and interval for stats
            fetchStats();
            setInterval(fetchStats, 15000);

            // Count-up animation for stat values (runs once when visible)
            function animateStatValues() {
                const els = document.querySelectorAll('.stat-value-anim');
                if (!els || !window.requestAnimationFrame) return;

                const nf = new Intl.NumberFormat();

                const animate = (el) => {
                    const target = Number(el.dataset.target || 0);
                    const duration = 480; // ms (shorter)
                    const start = performance.now();
                    const from = 0;
                    const step = (now) => {
                        const t = Math.min((now - start) / duration, 1);
                        const eased = t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t; // easeInOutQuad-ish
                        const value = Math.round(from + (target - from) * eased);
                        el.textContent = nf.format(value);
                        if (t < 1) requestAnimationFrame(step);
                        else {
                            // tiny pulse
                            el.parentElement && el.parentElement.classList && el.parentElement.classList.add(
                                'stat-done');
                            setTimeout(() => el.parentElement && el.parentElement.classList && el.parentElement
                                .classList.remove('stat-done'), 420);
                        }
                    };
                    requestAnimationFrame(step);
                };

                const obs = new IntersectionObserver((entries, observer) => {
                    entries.forEach(e => {
                        if (e.isIntersecting) {
                            const el = e.target;
                            animate(el);
                            observer.unobserve(el);
                        }
                    });
                }, {
                    threshold: 0.2
                });

                els.forEach(el => {
                    // initialize with 0 or formatted
                    el.textContent = '0';
                    obs.observe(el);
                });
            }

            // Run after DOM is ready
            document.addEventListener('DOMContentLoaded', () => setTimeout(animateStatValues, 120));
        </script>
    </main>
@endsection

@push('scripts')
    <script>
        (function() {
            var tpl = `
<div id="hr-assistant" style="position:fixed;right:18px;bottom:18px;z-index:1200">
    <style>
        @keyframes hr-badge-pulse { 0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(14,165,233,0.6);} 70% { transform: scale(1.05); box-shadow: 0 0 0 8px rgba(14,165,233,0);} 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(14,165,233,0);} }
        .hr-assistant-badge { position: absolute; right: -6px; top: -6px; background: #ef4444; color: #fff; min-width: 18px; height:18px; display:inline-flex; align-items:center; justify-content:center; font-size:11px; padding:0 5px; border-radius:999px; box-shadow: 0 4px 10px rgba(2,6,23,0.12); }
        .hr-assistant-badge.pulse { animation: hr-badge-pulse 1.2s infinite; }
        .hr-assistant-toggle-wrap { position: relative; display:inline-block }
    </style>
    <div id="hr-assistant-toggle" aria-label="Open HR Assistant" title="HR Assistant" style="background:#0ea5e9;color:#fff;padding:10px;display:flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:999px;cursor:pointer;box-shadow:0 8px 20px rgba(2,6,23,0.12)">
        <div class="hr-assistant-toggle-wrap">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span id="hr-assistant-badge" class="hr-assistant-badge" style="display:none">0</span>
        </div>
    </div>
            <div id="hr-assistant-panel" style="display:none;width:320px;background:#fff;border-radius:12px;box-shadow:0 20px 40px rgba(2,6,23,0.2);overflow:hidden;margin-top:8px">
                    <div style="padding:10px;border-bottom:1px solid #eee;background:#f8fafc;font-weight:600">HR Assistant</div>
                    <div id="hr-assistant-messages" style="max-height:200px;overflow:auto;padding:10px;font-size:14px"></div>
                    <div style="padding:8px;border-top:1px solid #eee;display:flex;flex-wrap:wrap;gap:8px;align-items:center">
                        <div style="flex:1;display:flex;flex-direction:column">
                            <div style="margin-bottom:6px;font-size:13px;color:#6b7280">Try a question</div>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                <button class="hr-suggest btn-suggest" data-q="How many leave days do I have left?" style="background:#fff;border:1px solid #e5e7eb;padding:6px 10px;border-radius:8px;cursor:pointer">How many leave days do I have left?</button>
                                <button class="hr-suggest btn-suggest" data-q="How do I apply for leave?" style="background:#fff;border:1px solid #e5e7eb;padding:6px 10px;border-radius:8px;cursor:pointer">How do I apply for leave?</button>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px">
                            <input id="hr-assistant-input" placeholder="Ask: 'How many leave days do I have left?'" style="flex:1;padding:8px;border:1px solid #e5e7eb;border-radius:8px;margin-right:8px" />
                            <button id="hr-assistant-send" style="background:#0ea5e9;color:#fff;padding:8px 10px;border-radius:8px;border:0">Send</button>
                        </div>
                    </div>
                </div>`; <
            /div>`;

        document.body.insertAdjacentHTML('beforeend', tpl);
        bindSuggestors();
        var toggle = document.getElementById('hr-assistant-toggle');
        var panel = document.getElementById('hr-assistant-panel');
        var messages = document.getElementById('hr-assistant-messages');
        var input = document.getElementById('hr-assistant-input');
        var send = document.getElementById('hr-assistant-send');

        function bindSuggestors() {
            var s = document.querySelectorAll('.hr-suggest');
            s.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var q = btn.getAttribute('data-q');
                    if (!input) return;
                    input.value = q;
                    input.focus();
                });
            });
        }

        var badge = document.getElementById('hr-assistant-badge');

        function clearBadge() {
            if (!badge) return;
            badge.style.display = 'none';
            badge.textContent = '0';
            badge.classList.remove('pulse');
        }

        function incBadge() {
            if (!badge) return;
            var n = parseInt(badge.textContent || '0') + 1;
            badge.textContent = n;
            badge.style.display = 'inline-flex';
            badge.classList.add('pulse');
        }

        toggle.addEventListener('click', function() {
            var showing = panel.style.display === 'block';
            panel.style.display = showing ? 'none' : 'block';
            if (!showing) {
                clearBadge();
                bindSuggestors();
            }
        });

        function appendMsg(who, text) {
            var el = document.createElement('div');
            el.style.marginBottom = '8px';
            el.innerHTML =
                `<div style="font-size:12px;color:#6b7280;margin-bottom:3px">${who}</div><div style="background:${who==='You'?'#eef2ff':'#f3f4f6'};padding:8px;border-radius:8px">${text}</div>`;
            messages.appendChild(el);
            messages.scrollTop = messages.scrollHeight;
        }

        async function ask(message) {
            appendMsg('You', message);
            appendMsg('Assistant', 'Thinking...');
            try {
                const res = await fetch(`{{ route('assistant.hr') }}`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                            'content') || ''
                    },
                    body: JSON.stringify({
                        message
                    })
                });

                if (!res.ok) {
                    let txt = '';
                    try {
                        txt = await res.text();
                    } catch (e) {
                        txt = '';
                    }
                    throw new Error(
                        `Assistant request failed: ${res.status} ${res.statusText} ${txt ? ' - ' + txt.substring(0,300) : ''}`
                        );
                    }

                    const json = await res.json();
                    // replace last assistant 'Thinking...' with actual answer
                    var last = messages.lastElementChild;
                    if (last) last.remove();
                    appendMsg('Assistant', json.reply || 'No reply');
                    if (panel.style.display !== 'block') {
                        incBadge();
                    }
                } catch (e) {
                    var last = messages.lastElementChild;
                    if (last) last.remove();
                    var msg = 'Failed to contact assistant';
                    if (e && e.message) msg += ': ' + e.message;
                    appendMsg('Assistant', msg);
                    console.error('HR assistant error:', e);
                }
            }

            send.addEventListener('click', function() {
                var v = input.value && input.value.trim();
                if (!v) return;
                ask(v);
                input.value = '';
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    send.click();
                }
            });


        })();
    </script>
@endpush
