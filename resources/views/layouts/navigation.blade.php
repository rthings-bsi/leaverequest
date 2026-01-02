<nav x-data="{ open: false, collapsed: false }"
    x-effect="document.body.classList.toggle('no-sidebar', collapsed); document.body.classList.toggle('menu-open', open)"
    class="bg-transparent">
    <style>
        /* Desktop sidebar width */
        :root {
            --sidebar-w: 18rem;
        }

        /* Sidebar will overlay content on all sizes; do not push main with margin-left */

        /* Ensure on small screens the main content uses full width (no left gap) */
        @media (max-width: 639px) {
            main {
                margin-left: 0 !important;
                padding-left: 0 !important;
            }

            /* make sure sidebar remains hidden on small screens */
            .app-sidebar {
                display: none !important;
            }
        }

        /* On desktop the sidebar should not cover content — add left padding to main so
           centered content remains visible when the sidebar is present. */
        @media (min-width: 640px) {
            main {
                padding-left: var(--sidebar-w);
                transition: padding-left 220ms ease;
            }

            /* When collapsed use narrow padding so content shifts accordingly */
            body.no-sidebar main {
                padding-left: 4rem !important;
            }
        }

        /* Sidebar styling */
        .app-sidebar {
            width: var(--sidebar-w);
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 40;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        }

        .app-sidebar .brand {
            height: 140px;
        }

        /* Nav item visuals */
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0.75rem;
            border-radius: 0.75rem;
            transition: all 180ms ease;
        }

        .nav-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(2, 6, 23, 0.06);
        }

        .nav-item-active {
            background: linear-gradient(90deg, rgba(240, 232, 255, 0.9), rgba(250, 246, 255, 0.6));
            box-shadow: 0 8px 28px rgba(99, 102, 241, 0.06);
            border: 1px solid rgba(99, 102, 241, 0.08);
        }

        /* Icon tile */
        .icon-tile {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            box-shadow: 0 6px 14px rgba(2, 6, 23, 0.06);
        }

        .icon-tile svg {
            width: 20px;
            height: 20px;
        }

        /* Profile card */
        .profile-card {
            background: linear-gradient(180deg, #ffffff, #fbfbff);
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(2, 6, 23, 0.06);
            border: 1px solid rgba(15, 23, 42, 0.04);
        }

        .profile-name {
            font-weight: 700;
            color: #0f172a;
        }

        .profile-email {
            font-size: 0.85rem;
            color: #475569;
        }

        .btn-outline {
            border: 1px solid rgba(15, 23, 42, 0.06);
            background: #fff;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
        }

        .btn-danger {
            background: #ef476f;
            color: #fff;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
        }

        /* No automatic main margin adjustments — sidebar overlays the content */

        /* Icons-only collapsed sidebar (desktop) */
        .no-sidebar .app-sidebar {
            width: 4rem !important;
            transition: width 220ms ease;
            /* Hide any overflowing labels/text that could peek out */
            overflow: hidden;
        }

        /* Hide any small text and keep the brand/logo centered when collapsed */
        .no-sidebar .brand {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        /* When collapsed, add extra top padding so the reopen button doesn't overlap
           the logo or the notification icon. Also center stacked brand items. */
        .no-sidebar .brand {
            padding-top: 3rem !important;
        }

        .no-sidebar .brand .flex.flex-col {
            align-items: center;
        }

        .no-sidebar .brand a {
            justify-content: center;
        }

        /* hide app name */
        .no-sidebar .brand .text-lg {
            display: none !important;
        }

        /* hide the desktop close button inside brand when collapsed */
        .no-sidebar .brand button {
            display: none !important;
        }

        /* Nav items become icon-only */
        .no-sidebar .nav-item {
            justify-content: center;
            padding-left: 0.25rem;
            padding-right: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
        }

        /* hide labels and any extra inline text inside nav when collapsed */
        .no-sidebar .nav-label,
        .no-sidebar .app-sidebar .text-xs,
        .no-sidebar .app-sidebar .text-sm {
            display: none !important;
        }

        .no-sidebar .icon-tile {
            width: 40px;
            height: 40px;
            border-radius: 10px;
        }

        .no-sidebar .icon-tile svg {
            width: 20px;
            height: 20px;
        }

        /* Profile card collapsed: only show avatar */
        .no-sidebar .profile-card {
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 56px;
        }

        .no-sidebar .profile-card .profile-name,
        .no-sidebar .profile-card .profile-email,
        .no-sidebar .profile-card .mt-4,
        .no-sidebar .profile-card .flex-1,
        .no-sidebar .profile-card .btn-outline,
        .no-sidebar .profile-card form {
            display: none !important;
        }

        .no-sidebar .profile-card img,
        .no-sidebar .profile-card .w-14 {
            width: 40px;
            height: 40px;
        }

        /* Remove accidental text peeking from deeper DOM nodes */
        .no-sidebar .app-sidebar * {
            text-overflow: clip;
        }

        /* Ensure .brand can position child controls (reopen button) and add top spacing so logo sits lower */
        .brand {
            position: relative;
            padding-top: 0.9rem;
            padding-bottom: 0.35rem;
        }

        /* Re-open floating button: keep visible when collapsed and avoid overlapping the brand/logo */
        .no-sidebar button[x-show] {
            display: flex !important;
        }

        /* Reopen button positioning (placed beneath the logo inside the .brand region) */
        .reopen-button {
            position: absolute;
            z-index: 60;
            display: none;
            /* shown via x-show */
            left: 50%;
            transform: translateX(-50%);
            /* Place above the logo (inside brand) */
            top: 0.6rem;
            transition: left 180ms ease, top 180ms ease, transform 180ms ease;
        }

        /* When collapsed keep it visible and still above the logo (fits the compact layout) */
        .no-sidebar .reopen-button {
            display: flex !important;
            left: 50% !important;
            top: 0.6rem !important;
            transform: translateX(-50%) !important;
        }

        /* On very small screens keep it near the left edge and top (mobile slide-over) */
        @media (max-width: 639px) {
            .reopen-button {
                position: fixed;
                left: 1rem;
                top: 1rem;
                transform: none;
            }
        }

        /* When mobile menu is open, dim the main content and prevent page scroll */
        .menu-open {
            overflow: hidden;
        }

        .menu-open main {
            /* Remove brightness filter because we use a colored gradient backdrop */
            filter: none;
            transition: none;
        }
    </style>

    {{-- Mobile: render a top-stacked navigation on small screens (always visible) --}}

    <!-- Desktop Sidebar (shown from sm and up) -->
    <aside
        class="hidden sm:flex app-sidebar bg-gradient-to-b from-white/95 to-white/90 border-r border-white/80 shadow-xl flex-col">
        <div
            class="brand px-6 flex items-center justify-between border-b border-white/60 bg-gradient-to-r from-slate-50 to-white/95">
            <div class="flex items-center gap-3">
                <div class="flex items-start gap-3">
                    <div class="flex flex-col items-center sm:items-start">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center">
                            <x-application-logo class="h-9 w-auto" />
                        </a>

                        @auth
                            @php
                                $unreadCount = 0;
                                try {
                                    if (auth()->check() && \Illuminate\Support\Facades\Schema::hasTable('notifications')) {
                                        $unreadCount = auth()->user()->notifications()->whereNull('read_at')->count();
                                    }
                                } catch (\Throwable $e) {
                                    $unreadCount = 0;
                                }
                            @endphp

                            <a id="navNotificationsLink" href="{{ route('notifications.index') }}" title="Notifications" aria-label="Notifications"
                                class="mt-2 inline-flex items-center justify-center w-9 h-9 rounded-md text-slate-600 hover:text-slate-800 relative">
                                <svg fill="currentColor" viewBox="0 0 24 24" class="w-5 h-5 text-slate-500">
                                    <path d="M10 21h4a2 2 0 01-4 0zM18 8a6 6 0 10-12 0v5l-2 2v1h16v-1l-2-2V8z" />
                                </svg>
                                @if ($unreadCount > 0)
                                    <span id="navNotificationsBadge"
                                        class="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-[10px] font-semibold text-white bg-rose-500 rounded-full">{{ $unreadCount }}</span>
                                @endif
                                <span class="sr-only">Notifications</span>
                            </a>
                        @endauth
                    </div>

                    <div class="text-lg font-semibold text-slate-800 mt-1">{{ config('app.name', 'TimeRequest') }}</div>
                </div>
            </div>

            <!-- Desktop close button -->
            <button @click="collapsed = true" title="Close navigation"
                class="hidden sm:inline-flex items-center justify-center p-2 rounded-md text-slate-600 hover:bg-slate-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>

            <!-- Re-open compact sidebar button (shown when collapsed) - placed under logo -->
            <button x-show="collapsed" x-cloak @click="collapsed = false" title="Open navigation"
                class="reopen-button hidden sm:flex items-center justify-center w-10 h-10 rounded-full bg-white shadow-md border">
                <svg class="w-5 h-5 text-slate-700" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </button>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                try {
                    function attachMarkAllHandler(linkId, badgeId) {
                        var l = document.getElementById(linkId);
                        if (!l) return;
                        l.addEventListener('click', function(e) {
                            e.preventDefault();
                            var href = l.getAttribute('href') || '/notifications';
                            var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                            if (!csrf) {
                                window.location.href = href;
                                return;
                            }
                            fetch('/notifications/mark-all-read', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrf
                                },
                                body: JSON.stringify({})
                            }).then(function() {
                                try {
                                    var badge = document.getElementById(badgeId);
                                    if (badge) badge.remove();
                                } catch (err) {}
                                window.location.href = href;
                            }).catch(function() {
                                window.location.href = href;
                            });
                        });
                    }

                    attachMarkAllHandler('navNotificationsLink', 'navNotificationsBadge');
                    attachMarkAllHandler('mobileNotificationsLink', 'mobileNotificationsBadge');
                } catch (e) {
                    // noop
                }
            });
        </script>

        <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-2">
            <a href="{{ route('dashboard') }}" title="Dashboard" aria-label="Dashboard"
                class="nav-item {{ request()->routeIs('dashboard') ? 'nav-item-active' : '' }}">
                <span class="icon-tile">
                    <svg fill="currentColor" viewBox="0 0 24 24" class="text-violet-600">
                        <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zM13 21h8v-10h-8v10zm0-18v6h8V3h-8z" />
                    </svg>
                </span>
                <span class="nav-label text-sm font-medium text-slate-700">Dashboard</span>
            </a>

            <a href="{{ route('leave.create') }}" title="Leave Requests" aria-label="Leave Requests"
                class="nav-item {{ request()->routeIs('leave.*') ? 'nav-item-active' : '' }}">
                <span class="icon-tile">
                    <svg fill="currentColor" viewBox="0 0 24 24" class="text-cyan-600">
                        <path d="M7 2h10v2H7zM5 6h14v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6z" />
                    </svg>
                </span>
                <span class="nav-label text-sm font-medium text-slate-700">Leave Requests</span>
            </a>

            @auth
                <a href="{{ route('approvals.index') }}" title="Approvals" aria-label="Approvals"
                    class="nav-item {{ request()->routeIs('approvals.*') ? 'nav-item-active' : '' }}">
                    <span class="icon-tile">
                        <svg fill="currentColor" viewBox="0 0 24 24" class="text-emerald-600">
                            <path d="M9 12l2 2 4-4" />
                        </svg>
                    </span>
                    <span class="nav-label text-sm font-medium text-slate-700">Approvals</span>
                </a>

                @if (Auth::user()->hasAnyRole(['administrator', 'admin']))
                    <a href="{{ route('master.employees.index') }}" title="Master Employees" aria-label="Master Employees"
                        class="nav-item {{ request()->routeIs('master.employees.*') ? 'nav-item-active' : '' }}">
                        <span class="icon-tile">
                            <svg fill="currentColor" viewBox="0 0 24 24" class="text-slate-600">
                                <path d="M12 12a5 5 0 100-10 5 5 0 000 10zM2 20a10 10 0 0120 0H2z" />
                            </svg>
                        </span>
                        <span class="nav-label text-sm font-medium text-slate-700">Master Employees</span>
                    </a>
                @endif

                {{-- Notifications nav item removed; link placed under brand per request --}}
            @endauth
        </nav>

        <div class="px-4 py-6 border-t bg-white/90">
            @auth
                <div class="profile-card">
                    <div class="flex items-center gap-4">
                        @if (Auth::user()->avatar_path)
                            <img src="{{ asset('storage/' . Auth::user()->avatar_path) }}" alt="avatar"
                                class="w-14 h-14 rounded-full object-cover" />
                        @else
                            <div
                                class="w-14 h-14 rounded-full bg-slate-200 flex items-center justify-center text-xl font-semibold text-slate-700">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                        @endif

                        <div>
                            <div class="profile-name">{{ Auth::user()->name }}</div>
                            <div class="profile-email">{{ Auth::user()->email }}</div>
                            @php
                                $roleLabel = '';
                                try {
                                    if (method_exists(Auth::user(), 'getRoleNames')) {
                                        $r = Auth::user()->getRoleNames()->toArray();
                                        $roleLabel = count($r) ? implode(', ', $r) : '';
                                    }
                                } catch (\Throwable $e) {
                                    $roleLabel = '';
                                }
                            @endphp
                            @if ($roleLabel)
                                <div class="text-xs text-gray-500 mt-1">{{ $roleLabel }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-3">
                        @php
                            $user = Auth::user();
                        @endphp

                        <a href="{{ route('profile.edit') }}" class="btn-outline">Profile</a>

                        {{-- Approvals quick-action removed from profile card per request --}}

                        <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                            @csrf
                            <button type="submit" class="btn-danger">Log Out</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="flex gap-2">
                    <a href="{{ route('login') }}"
                        class="flex-1 text-center px-3 py-2 rounded-md bg-white border text-sm text-slate-700">Log in</a>
                    <a href="{{ route('register') }}"
                        class="flex-1 text-center px-3 py-2 rounded-md bg-white border text-sm text-slate-700">Register</a>
                </div>
            @endauth

            <div class="mt-4 text-xs text-slate-400">&copy; {{ date('Y') }} {{ config('app.name') }}</div>
        </div>
    </aside>

    <!-- Global backdrop (shows on both mobile & desktop when `open`) -->
    <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-40"
        style="background: linear-gradient(180deg, rgba(131,164,212,0.88) 0%, rgba(182,251,255,0.68) 100%);"
        @click="open = false"></div>

    <!-- Mobile top navigation (small screens) -->
    <div
        class="sm:hidden sticky top-0 z-40 bg-gradient-to-b from-white/95 to-white/90 border-b border-white/80 shadow-sm">
        <div class="px-4 py-4">
            <div class="flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <x-application-logo class="h-8 w-auto" />
                    <span class="font-semibold text-lg text-slate-800">{{ config('app.name', 'TimeRequest') }}</span>
                </a>

                <div class="flex items-center gap-2">
                    @auth
                        @php
                            $mobileUnread = 0;
                            try {
                                if (auth()->check() && \Illuminate\Support\Facades\Schema::hasTable('notifications')) {
                                    $mobileUnread = auth()->user()->notifications()->whereNull('read_at')->count();
                                }
                            } catch (\Throwable $e) {
                                $mobileUnread = 0;
                            }
                        @endphp

                        <a id="mobileNotificationsLink" href="{{ route('notifications.index') }}" title="Notifications" aria-label="Notifications"
                            class="inline-flex items-center justify-center p-2 rounded-md text-slate-600 hover:bg-slate-100 relative">
                            <svg fill="currentColor" viewBox="0 0 24 24" class="w-5 h-5 text-slate-700">
                                <path d="M10 21h4a2 2 0 01-4 0zM18 8a6 6 0 10-12 0v5l-2 2v1h16v-1l-2-2V8z" />
                            </svg>
                            @if ($mobileUnread > 0)
                                <span id="mobileNotificationsBadge" class="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-[10px] font-semibold text-white bg-rose-500 rounded-full">{{ $mobileUnread }}</span>
                            @endif
                            <span class="sr-only">Notifications</span>
                        </a>
                    @endauth

                    <!-- Mobile hamburger: toggle mobile nav -->
                    <button @click="open = !open" aria-label="Toggle navigation"
                        class="inline-flex items-center justify-center p-2 rounded-md text-slate-600 hover:bg-slate-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
            <p class="mt-2 text-sm text-slate-500">Let's to start it</p>
        </div>

        <!-- (backdrop moved out to global so it also appears on desktop) -->

        <!-- Slide-in panel (below header) -->
        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="-translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="-translate-y-4 opacity-0"
            class="fixed inset-x-4 top-16 z-50 bg-white rounded-2xl shadow-lg max-h-[75vh] overflow-auto">

            <nav class="px-4 py-4 space-y-4">
                <a href="{{ route('dashboard') }}" @click="open = false"
                    class="block bg-white rounded-2xl p-4 shadow-sm hover:shadow-md transition-shadow {{ request()->routeIs('dashboard') ? 'ring-1 ring-violet-200 bg-violet-50' : '' }}">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-white shadow-md flex items-center justify-center">
                            <svg class="w-6 h-6 text-violet-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zM13 21h8v-10h-8v10zm0-18v6h8V3h-8z" />
                            </svg>
                        </div>

                        <div class="flex-1">
                            <div class="text-sm font-medium text-slate-800">Dashboard</div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('leave.create') }}" @click="open = false"
                    class="block bg-white rounded-2xl p-4 shadow-sm hover:shadow-md transition-shadow {{ request()->routeIs('leave.*') ? 'ring-1 ring-amber-200 bg-amber-50' : '' }}">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-white shadow-md flex items-center justify-center">
                            <svg class="w-6 h-6 text-cyan-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M7 2h10v2H7zM5 6h14v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6z" />
                            </svg>
                        </div>

                        <div class="flex-1">
                            <div class="text-sm font-medium text-slate-800">Leave Requests</div>
                        </div>
                    </div>
                </a>

                @auth
                    <a href="{{ route('approvals.index') }}" @click="open = false"
                        class="block bg-white rounded-2xl p-4 shadow-sm hover:shadow-md transition-shadow {{ request()->routeIs('approvals.*') ? 'ring-1 ring-emerald-200 bg-emerald-50' : '' }}">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-lg bg-white shadow-md flex items-center justify-center">
                                <svg class="w-6 h-6 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 12l2 2 4-4" />
                                </svg>
                            </div>

                            <div class="flex-1">
                                <div class="text-sm font-medium text-slate-800">Approvals</div>
                            </div>
                        </div>
                    </a>

                    @if (Auth::user()->hasAnyRole(['administrator', 'admin']))
                        <a href="{{ route('master.employees.index') }}" @click="open = false"
                            class="block bg-white rounded-2xl p-4 shadow-sm hover:shadow-md transition-shadow {{ request()->routeIs('master.employees.*') ? 'ring-1 ring-slate-200 bg-slate-50' : '' }}">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-white shadow-md flex items-center justify-center">
                                    <svg class="w-6 h-6 text-slate-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 12a5 5 0 100-10 5 5 0 000 10zM2 20a10 10 0 0120 0H2z" />
                                    </svg>
                                </div>

                                <div class="flex-1">
                                    <div class="text-sm font-medium text-slate-800">Master Employees</div>
                                </div>
                            </div>
                        </a>
                    @endif

                        {{-- Notifications removed from mobile slide-in per request; icon moved to top bar --}}
                @endauth
            </nav>

            <div class="px-4 py-4 border-t bg-white/90">
                @auth
                    <div class="flex items-center gap-3">
                        @if (Auth::user()->avatar_path)
                            <img src="{{ asset('storage/' . Auth::user()->avatar_path) }}" alt="avatar"
                                class="w-12 h-12 rounded-full object-cover" />
                        @else
                            <div
                                class="w-12 h-12 rounded-full bg-slate-200 flex items-center justify-center text-lg font-semibold text-slate-700">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                        @endif

                        <div class="flex-1">
                            <div class="text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-slate-500">{{ Auth::user()->email }}</div>
                        </div>
                    </div>

                    <div class="mt-3 flex gap-3">
                        <a href="{{ route('profile.edit') }}"
                            class="flex-1 text-center px-3 py-2 rounded-md bg-white border text-sm text-slate-700 hover:bg-slate-50">Profile</a>

                        <form method="POST" action="{{ route('logout') }}" class="flex-1">
                            @csrf
                            <button type="submit"
                                class="w-full px-3 py-2 rounded-md bg-rose-500 text-white text-sm hover:opacity-95">Log
                                Out</button>
                        </form>
                    </div>
                @else
                    <div class="flex gap-2">
                        <a href="{{ route('login') }}"
                            class="flex-1 text-center px-3 py-2 rounded-md bg-white border text-sm text-slate-700">Log
                            in</a>
                        <a href="{{ route('register') }}"
                            class="flex-1 text-center px-3 py-2 rounded-md bg-white border text-sm text-slate-700">Register</a>
                    </div>
                @endauth

                <div class="mt-4 text-xs text-slate-400">&copy; {{ date('Y') }} {{ config('app.name') }}</div>
            </div>
        </div>
</nav>
