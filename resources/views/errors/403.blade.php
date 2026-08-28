@php
    // <x-app-layout> nganggep ada user login (sidebar/topbar baca
    // auth()->user()->role dkk tanpa null-guard) -- aman selama ini karena
    // tiap 403 di app ini emang selalu kejadian abis login. TAPI middleware
    // EnsureRole juga abort(403) buat request TANPA user sama sekali, bukan
    // cuma role salah, jadi kalau suatu saat ada route baru yang kena
    // role:admin tapi lupa dibungkus middleware auth, guest yang kena 403
    // bakal bikin layout itu crash (fatal error, bukan 403 yang rapi). Guard
    // di sini biar guest tetap dapet halaman yang bener, bukan 500.
@endphp
@if (auth()->check())
    <x-app-layout>
        <x-slot name="header">
            <h2 class="font-extrabold text-lg text-gray-800">Akses Ditolak</h2>
        </x-slot>

        <div class="p-7">
            <div class="max-w-md mx-auto text-center py-14">
                <div class="w-16 h-16 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-5">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM7 11V7a5 5 0 0110 0v4"></path></svg>
                </div>
                <h3 class="font-extrabold text-lg text-gray-800 mb-2">Bukan Wewenang Anda</h3>
                <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                    {{ $exception->getMessage() ?: 'Maaf, Anda tidak punya akses ke halaman atau data ini.' }}
                </p>
                <x-button :href="route('dashboard')">Kembali ke Dashboard</x-button>
            </div>
        </div>
    </x-app-layout>
@else
    <div class="min-h-screen flex items-center justify-center bg-gray-50 p-7">
        <div class="max-w-md w-full text-center bg-white border border-gray-200 rounded-2xl p-10">
            <div class="w-16 h-16 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM7 11V7a5 5 0 0110 0v4"></path></svg>
            </div>
            <h3 class="font-extrabold text-lg text-gray-800 mb-2">Bukan Wewenang Anda</h3>
            <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                {{ $exception->getMessage() ?: 'Maaf, Anda tidak punya akses ke halaman atau data ini.' }}
            </p>
            <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg font-semibold whitespace-nowrap transition bg-cakrawala text-white border border-transparent hover:bg-nusantara px-4 py-2 text-sm">Ke Halaman Login</a>
        </div>
    </div>
@endif
