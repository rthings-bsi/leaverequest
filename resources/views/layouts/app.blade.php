<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <!-- Fallback: static compiled CSS so the app layout doesn't break when Vite dev server isn't running.
             To generate this file run in your project root:
                 npx tailwindcss -i resources/css/app.css -o public/css/tailwind.css --minify
             or run the full production build: npm run build
        -->
        <link rel="stylesheet" href="{{ asset('css/tailwind.css') }}">
    @endif

    {{-- Alpine fallback: if the compiled JS didn't initialize Alpine (e.g. Vite not running or assets missing),
         load a small CDN copy so interactive components (dropdowns, etc.) keep working. This is a low-risk
         fallback that only loads when window.Alpine is not present. --}}
    <script>
        (function() {
            try {
                if (typeof window !== 'undefined' && !window.Alpine) {
                    var s = document.createElement('script');
                    s.src = 'https://unpkg.com/alpinejs@3.15.1/dist/cdn.min.js';
                    s.defer = true;
                    s.onload = function() {
                        if (window.Alpine && typeof window.Alpine.start === 'function') {
                            window.Alpine.start();
                        }
                    };
                    document.head.appendChild(s);
                }
            } catch (e) {
                // noop
            }
        })();
    </script>
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        <style>
            /* Back-button styling: keep in-flow but avoid overlaying the sidebar/navigation.
               Use z-index less than the sidebar so it doesn't sit on top of nav items. */
            .back-button {
                position: relative;
                z-index: 30; /* below .app-sidebar (z-40) */
            }

            /* If mobile, ensure it still flows normally */
            @media (max-width: 639px) {
                .back-button {
                        z-index: 30;
                        position: relative;
                    }
            }
        </style>
        {{-- Don't show the main navigation on the register page (and other guest-only auth pages) --}}
        @unless (request()->routeIs('register') ||
                request()->routeIs('login') ||
                request()->routeIs('password.request') ||
                request()->routeIs('password.reset'))
            @include('layouts.navigation')
        @endunless

        {{-- Back button for authenticated pages is rendered inside the header (if present) to align with the page title. --}}

        {{-- Flash / toast messages (success / error) - visible to HR/Admin only --}}
        @if (auth()->check() && method_exists(auth()->user(), 'hasAnyRole') && auth()->user()->hasAnyRole(['administrator', 'admin', 'hr']))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="fixed top-6 right-6 z-50">
                @if (session('success'))
                    <div class="bg-emerald-600 text-white px-4 py-2 rounded-lg shadow-lg">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="bg-red-600 text-white px-4 py-2 rounded-lg shadow-lg">{{ session('error') }}</div>
                @endif
            </div>
        @endif

        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            {{ $header }}
                        </div>

                        {{-- notifications removed for all accounts per request --}}
                    </div>
                </div>
            </header>
        @endisset

        {{-- If a page does not provide a header, still render a back-button aligned to the page container
             for all pages except the excluded routes (dashboard, login, register, forgot password) --}}
        {{-- Back-button is rendered inside the main content area (below) so it sits directly above forms/content.
             We still exclude auth/guest routes and the dashboard. --}}

        <!-- Page Content -->
        <main class="w-full">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                {{-- Back-button placed inside the main container so it appears directly above forms/content. --}}
                @unless (request()->routeIs('register') ||
                        request()->routeIs('login') ||
                        request()->routeIs('password.request') ||
                        request()->routeIs('password.reset') ||
                        request()->routeIs('dashboard'))
                    @php
                        $prev = url()->previous();
                        $current = request()->fullUrl();
                        try {
                            $prevHost = parse_url($prev, PHP_URL_HOST) ?: null;
                            $currentHost = parse_url($current, PHP_URL_HOST) ?: null;
                        } catch (\Throwable $e) {
                            $prevHost = null;
                            $currentHost = null;
                        }

                        if (
                            empty($prev) ||
                            $prev === $current ||
                            ($prevHost && $currentHost && $prevHost !== $currentHost)
                        ) {
                            $prev = route('dashboard');
                        }
                    @endphp

                    <div class="back-button mb-4">
                        <a href="{{ $prev }}" onclick="event.preventDefault(); history.back();"
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-md shadow-sm text-sm"
                            style="background: linear-gradient(90deg, #83A4D4 0%, #B6FBFF 100%); color: #08203a;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            {{ __('Back') }}
                        </a>
                    </div>
                @endunless

                @if (isset($slot))
                    {{ $slot }}
                @else
                    @yield('content')
                @endif
            </div>
        </main>
    </div>
    @auth
        <script src="https://js.pusher.com/8.0/pusher.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.11.0/echo.iife.js"></script>
        <script>
            (function() {
                try {
                    window.Laravel = {
                        userId: {{ auth()->id() }}
                    };

                    // Configure Pusher/Echo using config values when available
                    const pusherKey = '{{ config('broadcasting.connections.pusher.key') }}';
                    const pusherCluster = '{{ config('broadcasting.connections.pusher.options.cluster') ?? '' }}';

                    if (pusherKey) {
                        Pusher.logToConsole = false;
                        window.Echo = new window.Echo({
                            broadcaster: 'pusher',
                            key: pusherKey,
                            cluster: pusherCluster || undefined,
                            forceTLS: {{ config('app.env') === 'production' ? 'true' : 'false' }},
                            encrypted: {{ config('app.env') === 'production' ? 'true' : 'false' }},
                            auth: {
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            }
                        });

                        // Subscribe to the private notifications channel for the user
                        window.Echo.private('App.Models.User.' + window.Laravel.userId)
                            .notification(function(notification) {
                                // Simple toast: append to body
                                const toast = document.createElement('div');
                                toast.style.position = 'fixed';
                                toast.style.right = '20px';
                                toast.style.top = '20px';
                                toast.style.zIndex = 9999;
                                toast.style.background = '#111827';
                                toast.style.color = '#fff';
                                toast.style.padding = '12px 16px';
                                toast.style.borderRadius = '8px';
                                toast.style.boxShadow = '0 6px 18px rgba(0,0,0,0.15)';
                                toast.innerText = notification.data?.message || notification.type || 'New notification';
                                document.body.appendChild(toast);
                                setTimeout(() => toast.remove(), 6000);
                            });

                        // Also listen on the public approvals channel to refresh dashboard and lists
                        try {
                            if (window.Echo.channel) {
                                const approvalsChannel = window.Echo.channel('approvals');
                                approvalsChannel.listen('ApprovalCreated', function(e) {
                                    try {
                                        // If the event contains aggregated counts, update stat cards directly to avoid an extra fetch
                                        if (e && (e.total !== undefined || e.pending !== undefined)) {
                                            try {
                                                var mapping = {
                                                    total: 'total',
                                                    pending: 'pending',
                                                    accepted: 'accepted',
                                                    rejected: 'rejected'
                                                };
                                                var vals = Object.keys(mapping).map(function(k) {
                                                    return Number(e[k] || 0);
                                                });
                                                var max = vals.length ? Math.max.apply(null, vals.concat([1])) : 1;

                                                Object.keys(mapping).forEach(function(key) {
                                                    var card = document.querySelector('.stat-card[data-stat="' + mapping[key] + '"]');
                                                    if (!card) return;
                                                    var val = Number(e[key] || 0);
                                                    var valEl = card.querySelector('.stat-value');
                                                    if (valEl) valEl.textContent = val;

                                                    var pct = max ? Math.round((val / max) * 100) : 0;
                                                    var pctLabel = card.querySelector('.stat-bar-label');
                                                    if (pctLabel) pctLabel.textContent = pct + '%';

                                                    var barFill = card.querySelector('.stat-bar-fill');
                                                    if (barFill) {
                                                        barFill.setAttribute('data-width', pct);
                                                        requestAnimationFrame(function() {
                                                            barFill.style.width = pct + '%';
                                                            var accent = getComputedStyle(card).getPropertyValue('--accent-rgb') || '';
                                                            if (pct >= 100 && accent) {
                                                                barFill.style.background = 'linear-gradient(90deg, rgb(' + accent.trim() + '), rgba(' + accent.trim() + ',0.9))';
                                                            }
                                                        });
                                                    }
                                                });

                                                // Update Go Green tracker
                                                var goGreen = document.getElementById('go-green');
                                                if (goGreen && e.total !== undefined) {
                                                    var paper = Number(e.total || 0);
                                                    var trees = paper / 100;
                                                    goGreen.setAttribute('data-paper', paper);
                                                    goGreen.setAttribute('data-trees', trees);
                                                    var statsEl = document.getElementById('go-green-stats');
                                                    if (statsEl) statsEl.innerHTML = 'Saved <strong>' + paper + '</strong> sheets • ~<strong>' + trees.toFixed(2) + '</strong> small trees';
                                                }
                                            } catch (err) {
                                                console.warn('Failed to apply aggregated stats from ApprovalCreated', err);
                                            }
                                        } else {
                                            // Fallback: trigger existing fetch functions
                                            if (typeof fetchStats === 'function') {
                                                try { fetchStats(); } catch (err) { console.warn('fetchStats failed', err); }
                                            }
                                            if (typeof fetchTodayData === 'function') {
                                                try { fetchTodayData(); } catch (err) { console.warn('fetchTodayData failed', err); }
                                            }
                                        }

                                        // Update individual leave list items if present on the page
                                        if (e && e.leave_id) {
                                            var el = document.querySelector('[data-leave-id="' + e.leave_id + '"]');
                                            if (el) {
                                                // find any status badge inside and update text to the action or new final_status
                                                var badge = el.querySelector('.status-badge');
                                                if (badge) {
                                                    badge.textContent = (e.action || 'Updated').toString().charAt(0).toUpperCase() + (e.action || 'Updated').toString().slice(1);
                                                    // adjust classes for a simple approved/rejected/pending color change
                                                    var st = (e.action || '').toString().toLowerCase();
                                                    badge.className = 'status-badge px-3 py-1 rounded-full text-sm ' + (st.startsWith('app') ? 'bg-emerald-100 text-emerald-700' : (st.startsWith('rej') ? 'bg-rose-100 text-rose-700' : (st === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-700')));
                                                }

                                                    // update known timestamp fields inside the element (use data-field to map)
                                                    var fields = ['created_at', 'updated_at', 'supervisor_approved_at', 'manager_approved_at', 'hr_notified_at'];
                                                    fields.forEach(function(f) {
                                                        var node = el.querySelector('time.local-time[data-field="' + f + '"]');
                                                        if (!node) return;
                                                        var val = e[f] || null;
                                                        if (val) {
                                                            try {
                                                                node.setAttribute('datetime', val);
                                                            } catch (err) {
                                                                // ignore
                                                            }
                                                        }
                                                    });
                                                    if (typeof window.renderLocalTimes === 'function') {
                                                        try { window.renderLocalTimes(); } catch (err) { /* ignore */ }
                                                    }
                                            }
                                        }
                                    } catch (err) {
                                        console.error('Approvals channel handler failed', err);
                                    }
                                });
                                // Also listen for new leave submissions so dashboards update immediately
                                approvalsChannel.listen('LeaveCreated', function(e) {
                                    try {
                                        if (!e) return;
                                        ['total','pending','accepted','rejected'].forEach(function(k) {
                                            var card = document.querySelector('.stat-card[data-stat="' + k + '"]');
                                            if (!card) return;
                                            var val = Number(e[k] || 0);
                                            var valEl = card.querySelector('.stat-value');
                                            if (valEl) valEl.textContent = val;
                                            var vals = [Number(e.total||0), Number(e.pending||0), Number(e.accepted||0), Number(e.rejected||0)];
                                            var max = vals.length ? Math.max.apply(null, vals.concat([1])) : 1;
                                            var pct = max ? Math.round((val / max) * 100) : 0;
                                            var pctLabel = card.querySelector('.stat-bar-label');
                                            if (pctLabel) pctLabel.textContent = pct + '%';
                                            var barFill = card.querySelector('.stat-bar-fill');
                                            if (barFill) { barFill.setAttribute('data-width', pct); requestAnimationFrame(function(){ barFill.style.width = pct + '%'; }); }
                                        });
                                        var goGreen = document.getElementById('go-green');
                                        if (goGreen && e.total !== undefined) {
                                            var paper = Number(e.total || 0);
                                            var trees = paper / 100;
                                            var tEl = goGreen.querySelector('.go-green-trees');
                                            if (tEl) tEl.textContent = trees.toFixed(2);
                                        }
                                    } catch (err) {
                                        // ignore
                                    }
                                });
                            }
                        } catch (e) {
                            // ignore
                        }
                    }
                } catch (e) {
                    console.warn('Realtime notifications failed to initialize', e);
                }
            })
            ();
        </script>
    @endauth
    <script>
        // Global realtime time renderer for all pages.
        (function() {
            // keep existing value if defined by a view
            window.serverOffset = window.serverOffset || 0;

            window.renderLocalTimes = window.renderLocalTimes || function() {
                const nodes = document.querySelectorAll('time.local-time');
                const now = new Date(Date.now() + (window.serverOffset || 0));
                nodes.forEach(function(node) {
                    const iso = node.getAttribute('datetime');
                    if (!iso) return;
                    const d = new Date(iso);
                    if (isNaN(d.getTime())) return;

                    // Compute relative time (human friendly) and fall back to absolute for older dates
                    const diffSec = Math.floor((now.getTime() - d.getTime()) / 1000);
                    let text = '';
                    if (diffSec < 5) {
                        text = 'just now';
                    } else if (diffSec < 60) {
                        text = diffSec + 's ago';
                    } else if (diffSec < 3600) {
                        const m = Math.floor(diffSec / 60);
                        const s = diffSec % 60;
                        text = m + 'm ' + s + 's ago';
                    } else if (diffSec < 86400) {
                        const h = Math.floor(diffSec / 3600);
                        const m = Math.floor((diffSec % 3600) / 60);
                        text = h + 'h ' + m + 'm ago';
                    } else {
                        // older than 1 day: show absolute date
                        const df = new Intl.DateTimeFormat(undefined, {
                            year: 'numeric',
                            month: 'short',
                            day: '2-digit'
                        });
                        const tf = new Intl.DateTimeFormat(undefined, {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        text = df.format(d) + ' ' + tf.format(d);
                    }

                    node.textContent = text;
                    // Keep absolute timestamp on hover
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

            // initial render and resync
            try {
                window.renderLocalTimes();
            } catch (e) {}
            resyncServerTime();

            // update times every second
            setInterval(function() {
                try {
                    window.renderLocalTimes();
                } catch (e) {}
            }, 1000);

            // resync every 5 minutes
            setInterval(resyncServerTime, 5 * 60 * 1000);
        })();
    </script>
</body>

</html>
