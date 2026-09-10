<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Setup Verifikasi 2 Langkah - {{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="flex min-h-screen w-full items-center justify-center bg-gray-50 p-6">
            <div class="w-full max-w-md bg-white border border-gray-200 rounded-2xl p-8">
                <h1 class="text-xl font-extrabold text-gray-800 mb-1.5">Setup Verifikasi 2 Langkah</h1>
                <p class="text-sm text-gray-500 mb-6">
                    Akun admin wajib pakai verifikasi 2 langkah (MFA). Scan QR code ini pakai aplikasi authenticator
                    (Google Authenticator, Microsoft Authenticator, dll), lalu masukin kode 6 digit yang muncul buat konfirmasi.
                </p>

                <div class="flex justify-center mb-5">
                    <img src="{{ $qrCodeDataUri }}" alt="QR Code Setup MFA" class="w-52 h-52 border border-gray-100 rounded-xl">
                </div>

                <details class="mb-6 text-xs text-gray-400">
                    <summary class="cursor-pointer font-semibold text-gray-500">Gak bisa scan QR? Masukin manual</summary>
                    <p class="font-mono mt-1.5 p-2 bg-gray-50 rounded-lg break-all">{{ $secret }}</p>
                </details>

                <x-input-error :messages="$errors->get('kode')" class="mb-3" />

                <form method="POST" action="{{ route('two-factor.confirm') }}">
                    @csrf
                    <x-input-label for="kode" value="Kode dari Authenticator App" />
                    <x-text-input id="kode" class="block mt-1.5 w-full rounded-lg text-center tracking-[0.3em] font-mono text-lg" type="text" inputmode="numeric" name="kode" maxlength="6" required autofocus autocomplete="one-time-code" />

                    <button type="submit" class="mt-5 w-full px-6 py-2.5 rounded-lg bg-cakrawala text-white text-sm font-bold hover:bg-nusantara transition">
                        Konfirmasi &amp; Aktifkan
                    </button>
                </form>
            </div>
        </div>
    </body>
</html>
