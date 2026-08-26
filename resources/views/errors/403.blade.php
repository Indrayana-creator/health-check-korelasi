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
