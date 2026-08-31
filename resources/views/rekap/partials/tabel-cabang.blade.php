{{-- Dipakai bareng buat versi "Minggu Ini" & "Bulan Ini" di rekap/index.blade.php --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <x-card padding="p-5">
        <div class="w-[38px] h-[38px] rounded-[10px] bg-nusantara/10 text-nusantara flex items-center justify-center mb-3">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"></path></svg>
        </div>
        <p class="text-xs font-semibold text-gray-500 mb-0.5">Total Cabang</p>
        <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($stat['total_cabang'], 0, ',', '.') }}</p>
    </x-card>
    <x-card padding="p-5">
        <div class="w-[38px] h-[38px] rounded-[10px] bg-green-100 text-green-600 flex items-center justify-center mb-3">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M9 12l2 2 4-4M12 22a10 10 0 100-20 10 10 0 000 20z"></path></svg>
        </div>
        <p class="text-xs font-semibold text-gray-500 mb-0.5">Rata-rata Compliance</p>
        <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ $stat['avg_compliance'] }}%</p>
    </x-card>
    <x-card padding="p-5">
        <div class="w-[38px] h-[38px] rounded-[10px] bg-red-100 text-red-600 flex items-center justify-center mb-3">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
        </div>
        <p class="text-xs font-semibold text-gray-500 mb-0.5">Perlu Perhatian</p>
        <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($stat['total_perlu_perhatian'], 0, ',', '.') }}</p>
    </x-card>
</div>

<x-compliance-legend />

<x-table-scroll-hint />
<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-cakrawala">
            <tr>
                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Cabang</th>
                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Uker Lapor</th>
                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Total Item</th>
                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">OK</th>
                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Not OK</th>
                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">N/A</th>
                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Belum</th>
                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Compliance</th>
                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($rekap as $r)
                <tr class="hover:bg-cakrawala/5">
                    <td class="px-4 py-3 text-sm font-semibold text-gray-800">{{ $r['cabang'] }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $r['jumlah_uker_lapor'] }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $r['total_item'] }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $r['ok'] }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $r['not_ok'] }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $r['na'] }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $r['belum'] }}</td>
                    <td class="px-4 py-3 text-sm font-bold text-gray-800">{{ $r['persen'] }}%</td>
                    <td class="px-4 py-3">
                        <x-badge :color="$r['status'] === 'SANGAT BAIK' ? 'green' : ($r['status'] === 'BAIK' ? 'yellow' : 'red')">
                            {{ $r['status'] }}
                        </x-badge>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-10 text-center">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"></path></svg>
                        <p class="text-gray-400 text-sm">Belum ada data health check.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
