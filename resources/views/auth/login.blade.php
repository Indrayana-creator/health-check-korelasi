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
                <img
                    src="{{ asset('images/DESAIN-infografis-TW-3-2022-layer_0022_Gedung-BRI-2022-IMG_9962-copy-3.png') }}"
                    alt="Gedung BRI"
                    class="absolute inset-0 w-full h-full object-cover object-bottom opacity-55 pointer-events-none select-none"
                >
                {{-- Scrim tipis & rata dari atas ke bawah -- cuma buat jaga
                     kontras minimum, BUKAN nutupin foto (sebelumnya 95% di
                     atas bikin foto ketutup total dekat logo, itu penyebab
                     "gap kosong" yang dilaporkan). Kontras teks utamanya
                     diamanin lewat text-shadow di tiap elemen teks, bukan
                     scrim tebal di seluruh panel. --}}
                <div class="absolute inset-0 bg-gradient-to-b from-nusantara/50 via-nusantara/20 to-cakrawala/55"></div>

                <div class="relative flex items-center gap-3 [text-shadow:0_2px_8px_rgb(0_0_0_/_45%)]">
                    <div class="w-11 h-11 rounded-xl bg-white/15 flex items-center justify-center backdrop-blur-sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                        </svg>
                    </div>
                    <span class="text-xl font-extrabold tracking-tight">AsetSehat</span>
                </div>
                <div class="relative max-w-md [text-shadow:0_2px_10px_rgb(0_0_0_/_50%)]">
                    <p class="text-4xl leading-tight font-bold mb-4">Kelola aset &amp; health check cabang, dalam satu tempat.</p>
                    <p class="text-[15px] leading-relaxed text-white/90">Pantau kondisi aset, kepatuhan pemeliharaan, dan status persetujuan seluruh unit kerja secara real-time.</p>
                </div>
                {{-- flex ROW (bukan kolom) sengaja -- <img> jadi flex item
                     yang main axis-nya horizontal, jadi gak kena masalah
                     align-items:stretch gepeng-in lebar kayak yang kejadian
                     di sidebar. Ukuran logo (h-4, object-contain) sama
                     persis kayak badge di sidebar biar konsisten. --}}
                <div class="relative flex items-center gap-2.5 [text-shadow:0_1px_6px_rgb(0_0_0_/_45%)]">
                    <p class="text-xs text-white/70">&copy; {{ date('Y') }} AsetSehat &middot; Internal Operations Tool</p>
                    <img
                        src="{{ asset('images/bri-logo-putih.png') }}"
                        alt="Bank Rakyat Indonesia"
                        class="h-4 w-auto object-contain opacity-70 select-none"
                    >
                </div>
            </div>

            <div class="flex-1 flex items-center justify-center min-h-screen p-10 bg-white">
                <div class="w-full max-w-sm">
                    <h1 class="text-2xl font-extrabold text-gray-800 mb-1.5">Masuk ke akun</h1>
                    <p class="text-sm text-gray-500 mb-7">Gunakan PN (Personal Number) dan kata sandi Anda untuk melanjutkan.</p>

                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    @if (session('sesi_aktif_token'))
                        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
                            <p class="text-sm text-yellow-800 mb-3">
                                Akun ini masih aktif di perangkat lain (aktivitas terakhir {{ session('sesi_aktif_sejak') }}). Lanjutkan login di sini? Sesi di perangkat lain akan otomatis logout.
                            </p>
                            <form method="POST" action="{{ route('login.confirm') }}" class="flex flex-wrap gap-2">
                                @csrf
                                <input type="hidden" name="token" value="{{ session('sesi_aktif_token') }}">
                                <button type="submit" class="px-4 py-2 rounded-lg bg-yellow-600 text-white text-sm font-bold hover:bg-yellow-700">Lanjutkan &amp; Logout Sesi Lain</button>
                                <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50">Batal</a>
                            </form>
                        </div>
                    @else
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div>
                            <x-input-label for="pn" value="PN (Personal Number)" />
                            <x-text-input id="pn" class="block mt-1.5 w-full rounded-lg" type="text" inputmode="numeric" name="pn" :value="old('pn')" required autofocus autocomplete="username" />
                            <x-input-error :messages="$errors->get('pn')" class="mt-2" />
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
                            {{-- Bukan link -- reset password lewat email sekarang gak
                                 realistis (login pakai PN, kebanyakan akun gak punya
                                 email sama sekali sejak field ini jadi opsional).
                                 Lupa password ditangani manual oleh admin lewat
                                 Kelola User, bukan self-service. --}}
                            <span class="text-sm text-gray-500">Lupa password? Hubungi admin.</span>

                            <button type="submit" class="px-6 py-2.5 rounded-lg bg-cakrawala text-white text-sm font-bold hover:bg-nusantara transition">
                                {{ __('Masuk') }}
                            </button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </body>
</html>
