@php

@endphp
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Forgot Password - TimeRequest</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased">
    <div class="min-h-screen relative bg-gradient-to-b from-blue-100 to-teal-100">
        <div class="hidden lg:block absolute inset-y-0 right-0 w-1/2 bg-cover bg-center"
            style="background-image: url('/images/login-left.jpg')"></div>

        <div class="min-h-screen flex items-center justify-center">
            <div id="auth-card"
                class="bg-white rounded-2xl shadow-lg w-full max-w-md p-8 mx-4 lg:mx-0 transform opacity-0 translate-y-4 transition-all duration-400">
                <div class="flex flex-col items-center mb-6">
                    <img src="/images/logo.png" alt="Jeda" class="w-28 h-auto mb-2" />
                    <h1 class="text-xl font-semibold">Forgot Password</h1>
                    <p class="text-sm text-gray-500">Enter your account email. We will send you a link to reset your
                        password.</p>
                </div>

                @if (session('status'))
                    <div class="mb-4 p-3 rounded-md bg-green-50 border border-green-100 text-green-800 text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 p-3 rounded-md bg-red-50 border border-red-100 text-red-800 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input name="email" type="email" required
                            class="w-full border-2 border-blue-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-300" />
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-400 text-white font-semibold rounded-xl py-3 mb-4 hover:bg-blue-500">Send
                        reset link</button>

                    <p class="text-center text-xs text-gray-500">Remember password? <a href="{{ route('login') }}"
                            class="underline">Back to login</a></p>
                </form>
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
