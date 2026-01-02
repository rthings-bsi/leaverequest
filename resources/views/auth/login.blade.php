@php
@endphp
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login - TimeRequest</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased">
    {{-- Toast container for success messages --}}
    @if (session('success'))
        <div id="toast-success" class="fixed top-5 right-5 z-50 max-w-sm">
            <div class="flex items-start gap-3 bg-white border border-green-100 rounded-lg shadow p-3">
                <div class="flex-shrink-0">
                    <div
                        class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-700 font-bold">
                        ✓</div>
                </div>
                <div class="flex-1 text-sm text-green-800">{{ session('success') }}</div>
                <button id="toast-close" class="text-green-500 hover:text-green-700">✕</button>
            </div>
        </div>
        <script>
            (function() {
                const toast = document.getElementById('toast-success');
                const close = document.getElementById('toast-close');
                if (!toast) return;
                const hide = () => {
                    toast.style.transition = 'opacity 300ms';
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 350);
                };
                const timer = setTimeout(hide, 4000);
                close.addEventListener('click', () => {
                    clearTimeout(timer);
                    hide();
                });
            })();
        </script>
    @endif
    <div class="min-h-screen relative bg-gradient-to-b from-blue-100 to-teal-100">
        <div class="hidden lg:block absolute inset-y-0 right-0 w-1/2 bg-cover bg-center"
            style="background-image: url('{{ asset('images/login.png') }}')"></div>

        <!-- keep centered on small screens, align right on md+ screens -->
        <div class="min-h-screen flex items-center justify-center md:justify-end">
            <div id="auth-card"
                class="bg-white rounded-2xl shadow-lg w-full max-w-md p-8 mx-4 lg:mx-0 transform opacity-0 translate-y-4 transition-all duration-400"
                style="--auth-right-offset:58vw;">
                <style>
                    @media (min-width: 768px) {
                        #auth-card {
                            margin-right: var(--auth-right-offset) !important;
                        }
                    }
                </style>
                @if (session('success'))
                    <div class="mb-4 p-3 rounded-md bg-green-50 border border-green-100 text-green-800 text-sm">
                        {{ session('success') }}
                    </div>
                @endif
                <div class="flex flex-col items-center mb-6">
                    <div class="flex items-center gap-6 mb-3">
                        <div class="w-32 h-32 rounded-full bg-white shadow-sm flex items-center justify-center p-3">
                            <img src="{{ asset('images/logo_ivssi.png') }}" alt="IVSSI" class="w-24 h-auto" />
                        </div>
                        <div class="w-32 h-32 rounded-full bg-white shadow-sm flex items-center justify-center p-3">
                            <img src="{{ asset('images/logo.png') }}" alt="TimeRequest" class="w-24 h-auto" />
                        </div>
                    </div>
                    <div class="text-center text-xs text-slate-600">
                        PT Indorama Ventures Sustainable Solutions Indonesia
                    </div>
                </div>

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    @if ($errors->any())
                        <div class="mb-4 p-3 rounded-md bg-red-50 border border-red-100 text-red-800 text-sm">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input name="email" type="email" required
                            class="w-full border border-blue-100 bg-gray-50 rounded-xl px-4 py-3 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-300" />
                    </div>

                    <div class="mb-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input name="password" type="password" required
                            class="w-full border border-blue-100 bg-gray-50 rounded-xl px-4 py-3 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-300" />
                    </div>

                    <div class="text-right mb-6">
                        <a href="{{ route('password.request') }}" class="text-sm text-gray-500 hover:underline">Forgot
                            password?</a>
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-400 text-white font-semibold rounded-xl py-3 mb-4 hover:bg-blue-500 shadow-md transform hover:-translate-y-0.5 transition">Login</button>

                    <p class="text-center text-xs text-gray-500">Need an account? Contact your HR.</p>
                </form>
            </div>
        </div>
    </div>
    <footer class="py-6 text-center text-sm text-gray-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            &copy; {{ date('Y') }} {{ config('app.name', 'TimeRequest') }}. All rights reserved.
        </div>
    </footer>
</body>
<script>
    (function() {
        const c = document.getElementById('auth-card');
        if (!c) return;
        requestAnimationFrame(() => {
            c.classList.remove('opacity-0');
            c.classList.remove('translate-y-4');
            c.style.transitionDuration = '400ms';
        });
    })();
</script>

</html>
