<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Aset Perlu Perhatian</h2>
        <p class="text-xs text-gray-500 mt-0.5">{{ $asetList->total() }} aset -- kondisi rusak/tidak layak, lewat umur pakai tapi belum ditandai PH, atau belum dicek &gt;6 bulan</p>
    </x-slot>

    <div class="p-7 space-y-4 max-w-[1360px]">

        <x-flash-status />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500 max-w-3xl">
                Gabungan 3 kondisi yang sebelumnya tersebar di filter terpisah, jadi 1 daftar kerja -- aset di sini bisa
                kena lebih dari 1 alasan sekaligus (lihat badge di kolom Alasan).
            </p>
            <x-button variant="secondary" :href="route('aset.index')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
                Kembali ke Data Aset
            </x-button>
        </div>

        <x-table-scroll-hint />
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">ASET ID</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Uker</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Merek / Type</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Kondisi</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Alasan</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($asetList as $aset)
                        @php
                            $rusak = in_array($aset->kondisi, ['RUSAK', 'TIDAK LAYAK']);
                            $lewatUmur = $aset->tahun_perolehan && $aset->tahun_perolehan <= $tahunAmbangPh && $aset->kondisi !== 'PH/DISMANTEL';
                            $belumDicek = ! $aset->kondisi_logs_max_created_at || \Illuminate\Support\Carbon::parse($aset->kondisi_logs_max_created_at)->lt($batasStale);
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-700">
                                <a href="{{ route('aset.show', $aset) }}" class="text-cakrawala hover:underline">{{ $aset->no_asset }}</a>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $aset->uker?->nama }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $aset->merek }} {{ $aset->tipe_model }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="match($aset->kondisi) { 'NORMAL' => 'green', 'RUSAK', 'TIDAK LAYAK' => 'red', default => 'gray' }">
                                    {{ $aset->kondisi ?? 'Belum Diisi' }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @if ($rusak)
                                        <x-badge color="red">Rusak/Tidak Layak</x-badge>
                                    @endif
                                    @if ($lewatUmur)
                                        <x-badge color="yellow">Lewat Umur Pakai (PH)</x-badge>
                                    @endif
                                    @if ($belumDicek)
                                        <x-badge color="gray">Belum Dicek &gt;6 Bulan</x-badge>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <x-icon-button variant="neutral" label="Lihat Detail" :href="route('aset.show', $aset)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </x-icon-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M20 6L9 17l-5-5"></path></svg>
                                <p class="text-gray-400 text-sm">Gak ada aset yang butuh perhatian -- semua aman.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $asetList->links() }}
        </div>
    </div>
</x-app-layout>
