<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Verifikasi 2 Langkah - {{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased" x-data="{ pakaiRecovery: false }">
        <div class="flex min-h-screen w-full items-center justify-center bg-gray-50 p-6">
            <div class="w-full max-w-sm bg-white border border-gray-200 rounded-2xl p-8">
                <h1 class="text-xl font-extrabold text-gray-800 mb-1.5">Verifikasi 2 Langkah</h1>
                <p class="text-sm text-gray-500 mb-6" x-show="!pakaiRecovery">Masukin kode 6 digit dari aplikasi authenticator Anda.</p>
                <p class="text-sm text-gray-500 mb-6" x-show="pakaiRecovery" x-cloak>Masukin salah satu kode cadangan yang Anda simpan pas setup awal.</p>

                <x-input-error :messages="$errors->get('kode')" class="mb-3" />

                <form method="POST" action="{{ route('two-factor.verify') }}">
                    @csrf
                    <template x-if="!pakaiRecovery">
                        <div>
                            <x-input-label for="kode" value="Kode Authenticator" />
                            <x-text-input id="kode" class="block mt-1.5 w-full rounded-lg text-center tracking-[0.3em] font-mono text-lg" type="text" inputmode="numeric" name="kode" maxlength="6" required autofocus autocomplete="one-time-code" />
                        </div>
                    </template>
                    <template x-if="pakaiRecovery">
                        <div>
                            <x-input-label for="kode-recovery" value="Kode Cadangan" />
                            <x-text-input id="kode-recovery" class="block mt-1.5 w-full rounded-lg text-center font-mono" type="text" name="kode" placeholder="ABCDE-FGHJK" required />
                        </div>
                    </template>

                    <button type="submit" class="mt-5 w-full px-6 py-2.5 rounded-lg bg-cakrawala text-white text-sm font-bold hover:bg-nusantara transition">
                        Verifikasi
                    </button>
                </form>

                <button type="button" class="mt-4 text-xs text-gray-400 hover:text-gray-600 underline" @click="pakaiRecovery = !pakaiRecovery">
                    <span x-show="!pakaiRecovery">HP hilang? Pakai kode cadangan</span>
                    <span x-show="pakaiRecovery" x-cloak>Pakai kode authenticator biasa</span>
                </button>
            </div>
        </div>
    </body>
</html>
