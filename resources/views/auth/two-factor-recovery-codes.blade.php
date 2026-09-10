<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Kode Cadangan MFA - {{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="flex min-h-screen w-full items-center justify-center bg-gray-50 p-6">
            <div class="w-full max-w-md bg-white border border-gray-200 rounded-2xl p-8">
                <div class="w-11 h-11 rounded-xl bg-green-100 text-green-600 flex items-center justify-center mb-4">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M20 6L9 17l-5-5"></path></svg>
                </div>
                <h1 class="text-xl font-extrabold text-gray-800 mb-1.5">MFA berhasil diaktifkan</h1>
                <p class="text-sm text-gray-500 mb-5">
                    Simpan {{ count($codes) }} kode cadangan ini di tempat aman. Tiap kode cuma bisa dipakai
                    <strong>satu kali</strong>, buat login kalau HP authenticator Anda hilang atau ganti. Halaman ini
                    <strong>gak akan ditampilkan lagi</strong> setelah ini.
                </p>

                <div class="grid grid-cols-2 gap-2 p-4 bg-gray-50 rounded-xl mb-5 font-mono text-sm text-gray-700">
                    @foreach ($codes as $code)
                        <span>{{ $code }}</span>
                    @endforeach
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="window.print()" class="flex-1 px-4 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Print / Simpan PDF
                    </button>
                    <a href="{{ route('dashboard') }}" class="flex-1 text-center px-4 py-2.5 rounded-lg bg-cakrawala text-white text-sm font-bold hover:bg-nusantara transition">
                        Sudah Disimpan, Lanjut
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>
