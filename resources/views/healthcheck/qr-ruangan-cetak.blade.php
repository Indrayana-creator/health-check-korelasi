<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">QR Ruangan</h2>
        <p class="text-xs text-gray-500 mt-0.5">{{ $uker->nama }} &middot; {{ $kategori }}</p>
    </x-slot>

    <div class="p-7">
        <div class="max-w-md mx-auto space-y-4">

            <div class="flex items-center justify-between flex-wrap gap-2 print:hidden">
                <x-button variant="secondary" :href="route('healthcheck.qrRuanganForm')" size="sm">&larr; Buat QR Lain</x-button>
                <x-button type="button" size="sm" onclick="window.print()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"></path></svg>
                    Print QR
                </x-button>
            </div>

            <x-card padding="p-6" class="print:hidden">
                <div class="text-center">
                    <img
                        src="{{ route('healthcheck.qrRuanganImage', ['uker_kode' => $uker->kode, 'kategori' => $kategori]) }}"
                        alt="QR Ruangan {{ $uker->nama }} - {{ $kategori }}"
                        class="w-56 h-56 mx-auto border border-gray-100 rounded-lg"
                    >
                    <p class="font-extrabold text-sm text-gray-800 mt-3">{{ $uker->nama }}</p>
                    <p class="text-xs text-gray-400">{{ $kategori }}</p>
                    <p class="text-xs text-gray-400 mt-2">Tempel QR ini di ruangan/lokasi fisik yang sesuai. Scan buat langsung buka form Health Check kategori ini.</p>
                </div>
            </x-card>

            <div class="hidden print:block text-center py-8">
                <img
                    src="{{ route('healthcheck.qrRuanganImage', ['uker_kode' => $uker->kode, 'kategori' => $kategori]) }}"
                    alt="QR Ruangan {{ $uker->nama }} - {{ $kategori }}"
                    class="w-64 h-64 mx-auto"
                >
                <p class="font-extrabold text-base mt-3">{{ $uker->nama }}</p>
                <p class="text-sm">{{ $kategori }}</p>
            </div>

        </div>
    </div>
</x-app-layout>
