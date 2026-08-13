<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            Rekap Permintaan Perangkat
        </h2>
    </x-slot>

    <div class="p-7 space-y-4 max-w-[1360px]">

        <x-page-tabs :tabs="[
            ['label' => 'Rekap Health Check', 'href' => route('rekap.cabang'), 'active' => false],
            ['label' => 'Rekap Aset', 'href' => route('rekap.aset'), 'active' => false],
            ['label' => 'Rekap Permintaan Perangkat', 'href' => route('rekap.permintaanPerangkat'), 'active' => true],
            ['label' => 'Struktur Organisasi', 'href' => route('uker-tree.index'), 'active' => false],
        ]" />

        <div class="flex items-center justify-between gap-3 flex-wrap">
            <p class="text-sm text-gray-500 max-w-3xl">
                Daftar permintaan perangkat/perbaikan yang diajukan cabang, per minggu kerja (Senin-Jumat).
            </p>
            <div class="flex items-center gap-2">
                <x-button variant="secondary" :href="route('rekap.permintaanPerangkat', ['minggu' => $mingguSebelumnya])">&larr; Minggu Sebelumnya</x-button>
                <span class="text-sm font-bold text-gray-700 whitespace-nowrap">{{ $labelMinggu }}</span>
                <x-button variant="secondary" :href="route('rekap.permintaanPerangkat', ['minggu' => $mingguSesudahnya])">Minggu Berikutnya &rarr;</x-button>
            </div>
            <div class="flex gap-2">
                <x-button variant="secondary" :href="route('rekap.permintaanPerangkat.export.excel', request()->query())">Export Excel</x-button>
                <x-button variant="secondary" :href="route('rekap.permintaanPerangkat.export.pdf', request()->query())">Export PDF</x-button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-cakrawala/10 text-cakrawala flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Total Minggu Ini</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ $totalMinggu }}</p>
            </x-card>
            @foreach (\App\Models\PermintaanPerangkat::DAFTAR_STATUS as $s)
                <x-card padding="p-5">
                    <div class="w-[38px] h-[38px] rounded-[10px] {{ $s === 'Done Terkirim' ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600' }} flex items-center justify-center mb-3">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M12 8v4l3 3M12 22a10 10 0 100-20 10 10 0 000 20z"></path></svg>
                    </div>
                    <p class="text-xs font-semibold text-gray-500 mb-0.5">{{ $s }}</p>
                    <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ $breakdownStatus[$s] }}</p>
                </x-card>
            @endforeach
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">No Nota Dinas</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Tanggal Request</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Fungsi Requester</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Jumlah</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Keterangan</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Uker</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($permintaanList as $p)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-semibold text-gray-800">{{ $p->no_nota_dinas }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $p->tanggal_request->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $p->fungsi_requester }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $p->jumlah }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="match($p->status) { 'Done Terkirim' => 'green', 'Pending LGA' => 'yellow', 'Pending ESO' => 'blue', default => 'gray' }">
                                    {{ $p->status }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 max-w-xs">{{ $p->keterangan }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $p->uker?->nama }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"></path></svg>
                                <p class="text-gray-400 text-sm">Gak ada permintaan perangkat di minggu ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
