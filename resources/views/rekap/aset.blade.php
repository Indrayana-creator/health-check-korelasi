<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            Rekap Aset per Cabang
        </h2>
    </x-slot>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

    <div class="p-7 space-y-4 max-w-[1360px]">

        <x-page-tabs :tabs="[
            ['label' => 'Rekap Health Check', 'href' => route('rekap.cabang'), 'active' => false],
            ['label' => 'Rekap Aset', 'href' => route('rekap.aset'), 'active' => true],
            ['label' => 'Rekap Permintaan Perangkat', 'href' => route('rekap.permintaanPerangkat'), 'active' => false],
            ['label' => 'Kartu Skor Cabang', 'href' => route('rekap.skorCabang'), 'active' => false],
            ['label' => 'Struktur Organisasi', 'href' => route('uker-tree.index'), 'active' => false],
        ]" />

        <div class="flex items-center justify-between flex-wrap gap-3">
            <p class="text-sm text-gray-500 max-w-3xl">
                Data digabungkan (roll-up) dari seluruh uker/unit yang berada di bawah cabang yang sama,
                diurutkan dari persentase aset "sehat" (kondisi Normal) paling rendah agar cabang yang paling
                butuh perhatian terlihat lebih dulu.
            </p>
            <div class="flex gap-2 flex-none">
                <x-button variant="secondary" :href="route('rekap.aset.export.excel')">Export Excel</x-button>
                <x-button variant="secondary" :href="route('rekap.aset.export.pdf')">Export PDF</x-button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-nusantara/10 text-nusantara flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Total Cabang</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($totalCabang, 0, ',', '.') }}</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-green-100 text-green-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M9 12l2 2 4-4M12 22a10 10 0 100-20 10 10 0 000 20z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Rata-rata % Sehat</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ $avgPersenSehat }}%</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-cakrawala/10 text-cakrawala flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Rata-rata % Data Lengkap</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ $avgPersenLengkap }}%</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-red-100 text-red-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Perlu Perhatian</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($totalPerluPerhatian, 0, ',', '.') }}</p>
            </x-card>
        </div>

        @if (array_sum($distribusiKondisi) > 0)
            <x-card>
                <h3 class="font-extrabold text-sm text-gray-800 mb-1">Distribusi Kondisi Aset</h3>
                <p class="text-xs text-gray-400 mb-3">Snapshot kondisi aset saat ini (bukan tren dari waktu ke waktu -- kondisi aset gak dicatat historinya).</p>
                <div class="h-64">
                    <canvas
                        x-data="{
                            init() {
                                // Peta label chart -> value kolom kondisi asli. 'Lainnya' sengaja
                                // gak diklikin -- itu gabungan banyak kondisi berbeda (termasuk
                                // yang belum diisi), gak ada 1 filter tunggal yang mewakilinya.
                                const kondisiParam = { Normal: 'NORMAL', Rusak: 'RUSAK', 'Tidak Layak': 'TIDAK LAYAK' };
                                new Chart(this.$el, {
                                    type: 'doughnut',
                                    data: {
                                        labels: @js(array_keys($distribusiKondisi)),
                                        datasets: [{
                                            data: @js(array_values($distribusiKondisi)),
                                            backgroundColor: ['#22C55E', '#EF4444', '#F59E0B', '#94A3B8'],
                                            borderWidth: 0,
                                        }],
                                    },
                                    options: {
                                        maintainAspectRatio: false,
                                        plugins: { legend: { position: 'right' } },
                                        onClick: (evt, elements, chart) => {
                                            if (!elements.length) return;
                                            const label = chart.data.labels[elements[0].index];
                                            if (kondisiParam[label]) {
                                                window.location.href = `{{ route('aset.index') }}?kondisi=${encodeURIComponent(kondisiParam[label])}`;
                                            }
                                        },
                                        onHover: (evt, elements, chart) => {
                                            const label = elements.length ? chart.data.labels[elements[0].index] : null;
                                            evt.native.target.style.cursor = (label && kondisiParam[label]) ? 'pointer' : 'default';
                                        },
                                    },
                                });
                            }
                        }"
                    ></canvas>
                </div>
            </x-card>
        @endif

        @if (count($trenKondisi) > 1)
            <x-card>
                <h3 class="font-extrabold text-sm text-gray-800 mb-1">Tren Perubahan Kondisi Aset</h3>
                <p class="text-xs text-gray-400 mb-3">Jumlah transisi kondisi per bulan (dari Riwayat Perubahan Kondisi) -- bukan snapshot, tapi ARAH pergerakannya: makin banyak "Baru Rusak" dibanding "Diperbaiki" berarti tren memburuk.</p>
                <div class="h-64">
                    <canvas
                        x-data="{
                            init() {
                                new Chart(this.$el, {
                                    type: 'line',
                                    data: {
                                        labels: @js(collect($trenKondisi)->pluck('label')),
                                        datasets: [
                                            {
                                                label: 'Baru Rusak',
                                                data: @js(collect($trenKondisi)->pluck('baru_rusak')),
                                                borderColor: '#EF4444',
                                                backgroundColor: 'rgba(239, 68, 68, .12)',
                                                fill: true,
                                                tension: .3,
                                            },
                                            {
                                                label: 'Diperbaiki',
                                                data: @js(collect($trenKondisi)->pluck('diperbaiki')),
                                                borderColor: '#22C55E',
                                                backgroundColor: 'rgba(34, 197, 94, .12)',
                                                fill: true,
                                                tension: .3,
                                            },
                                        ],
                                    },
                                    options: {
                                        maintainAspectRatio: false,
                                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                                        plugins: { legend: { position: 'bottom' } },
                                    },
                                });
                            }
                        }"
                    ></canvas>
                </div>
            </x-card>
        @endif

        <x-compliance-legend />

        <x-table-scroll-hint />
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-cakrawala">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Cabang</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Uker Lapor</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Total Aset</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Normal</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Rusak</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Tidak Layak</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Lainnya</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">% Sehat</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">% Data Lengkap</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rekap as $r)
                        <tr class="hover:bg-cakrawala/5">
                            <td class="px-4 py-3 text-sm font-semibold text-gray-800">{{ $r['cabang'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $r['jumlah_uker_lapor'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $r['total'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $r['normal'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $r['rusak'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $r['tidak_layak'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $r['lainnya'] }}</td>
                            <td class="px-4 py-3 text-sm font-bold text-gray-800">{{ $r['persen_sehat'] }}%</td>
                            <td class="px-4 py-3 text-sm font-bold text-gray-800">{{ $r['persen_lengkap'] }}%</td>
                            <td class="px-4 py-3">
                                <x-badge :color="$r['status'] === 'SANGAT BAIK' ? 'green' : ($r['status'] === 'BAIK' ? 'yellow' : 'red')">
                                    {{ $r['status'] }}
                                </x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-10 text-center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"></path></svg>
                                <p class="text-gray-400 text-sm">Belum ada data aset.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
