<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            Kartu Skor Cabang
        </h2>
    </x-slot>

    <div class="p-7 space-y-4 max-w-[1360px]">

        <x-page-tabs :tabs="[
            ['label' => 'Rekap Health Check', 'href' => route('rekap.cabang'), 'active' => false],
            ['label' => 'Rekap Aset', 'href' => route('rekap.aset'), 'active' => false],
            ['label' => 'Rekap Permintaan Perangkat', 'href' => route('rekap.permintaanPerangkat'), 'active' => false],
            ['label' => 'Kartu Skor Cabang', 'href' => route('rekap.skorCabang'), 'active' => true],
            ['label' => 'Struktur Organisasi', 'href' => route('uker-tree.index'), 'active' => false],
        ]" />

        <div class="flex items-center justify-between flex-wrap gap-3">
            <p class="text-sm text-gray-500 max-w-3xl">
                Gabungan 4 metrik yang udah dihitung di rekap masing-masing (Compliance Health Check bulan ini, % Aset
                Sehat, % Data Lengkap, % Permintaan Perangkat tepat waktu) jadi satu skor per cabang, diurutkan dari
                yang PALING BAIK -- beda dari rekap lain yang urutannya sengaja dari yang paling butuh perhatian.
            </p>
            <div class="flex gap-2 flex-none">
                <x-button variant="secondary" :href="route('rekap.skorCabang.export.excel')">Export Excel</x-button>
                <x-button variant="secondary" :href="route('rekap.skorCabang.export.pdf')">Export PDF</x-button>
            </div>
        </div>

        <x-table-scroll-hint />
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">#</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Cabang</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Compliance HC</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Aset Sehat</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Data Lengkap</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">SLA Permintaan Perangkat</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Skor Gabungan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($skor as $s)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">
                                @if ($loop->index === 0)
                                    <span title="Peringkat 1">&#129351;</span>
                                @elseif ($loop->index === 1)
                                    <span title="Peringkat 2">&#129352;</span>
                                @elseif ($loop->index === 2)
                                    <span title="Peringkat 3">&#129353;</span>
                                @else
                                    <span class="text-gray-400">{{ $loop->index + 1 }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-800">{{ $s['cabang'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $s['compliance_hc'] !== null ? $s['compliance_hc'].'%' : '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $s['persen_sehat'] !== null ? $s['persen_sehat'].'%' : '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $s['persen_lengkap'] !== null ? $s['persen_lengkap'].'%' : '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $s['sla_permintaan'] !== null ? $s['sla_permintaan'].'%' : '-' }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="$s['skor_gabungan'] >= 95 ? 'green' : ($s['skor_gabungan'] >= 80 ? 'yellow' : 'red')">
                                    {{ $s['skor_gabungan'] }}%
                                </x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M12 22a10 10 0 100-20 10 10 0 000 20zM12 6v6l4 2"></path></svg>
                                <p class="text-gray-400 text-sm">Belum ada data buat dihitung skornya.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
