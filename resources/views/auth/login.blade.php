<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="flex min-h-screen w-full">
            <div class="hidden lg:flex flex-1 flex-col justify-between bg-gradient-to-br from-nusantara to-cakrawala text-white p-14 relative overflow-hidden">
                <div class="relative flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-white/15 flex items-center justify-center">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                        </svg>
                    </div>
                    <span class="text-xl font-extrabold tracking-tight">AsetSehat</span>
                </div>
                <div class="relative max-w-md">
                    <p class="text-4xl leading-tight font-bold mb-4">Kelola aset &amp; health check cabang, dalam satu tempat.</p>
                    <p class="text-[15px] leading-relaxed text-white/80">Pantau kondisi aset, kepatuhan pemeliharaan, dan status persetujuan seluruh unit kerja secara real-time.</p>
                </div>
                <p class="relative text-xs text-white/55">&copy; {{ date('Y') }} AsetSehat &middot; Internal Operations Tool</p>
            </div>

            <div class="flex-1 flex items-center justify-center p-10 bg-white">
                <div class="w-full max-w-sm">
                    <h1 class="text-2xl font-extrabold text-gray-800 mb-1.5">Masuk ke akun</h1>
                    <p class="text-sm text-gray-500 mb-7">Gunakan email dan kata sandi Anda untuk melanjutkan.</p>

                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" class="block mt-1.5 w-full rounded-lg" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="password" :value="__('Password')" />
                            <x-text-input id="password" class="block mt-1.5 w-full rounded-lg"
                                            type="password"
                                            name="password"
                                            required autocomplete="current-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div class="block mt-4">
                            <label for="remember_me" class="inline-flex items-center">
                                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-cakrawala shadow-sm focus:ring-cakrawala" name="remember">
                                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-between mt-6">
                            @if (Route::has('password.request'))
                                <a class="text-sm text-gray-500 hover:text-gray-800 underline" href="{{ route('password.request') }}">
                                    {{ __('Forgot your password?') }}
                                </a>
                            @else
                                <span></span>
                            @endif

                            <button type="submit" class="px-6 py-2.5 rounded-lg bg-cakrawala text-white text-sm font-bold hover:bg-nusantara transition">
                                {{ __('Masuk') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>
