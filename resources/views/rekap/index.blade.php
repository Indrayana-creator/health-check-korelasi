<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            Rekap Health Check per Cabang
        </h2>
    </x-slot>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

    <div class="p-7 space-y-4 max-w-[1360px]">

        <x-page-tabs :tabs="[
            ['label' => 'Rekap Health Check', 'href' => route('rekap.cabang'), 'active' => true],
            ['label' => 'Rekap Aset', 'href' => route('rekap.aset'), 'active' => false],
            ['label' => 'Rekap Permintaan Perangkat', 'href' => route('rekap.permintaanPerangkat'), 'active' => false],
            ['label' => 'Kartu Skor Cabang', 'href' => route('rekap.skorCabang'), 'active' => false],
            ['label' => 'Struktur Organisasi', 'href' => route('uker-tree.index'), 'active' => false],
        ]" />

        <p class="text-sm text-gray-500 max-w-3xl">
            Data digabungkan (roll-up) dari seluruh uker/unit yang berada di bawah cabang yang sama,
            diurutkan dari compliance paling rendah agar cabang yang paling butuh perhatian terlihat lebih dulu.
        </p>

        <div x-data="{ periode: 'minggu' }">

            <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-1.5">
                    <div class="flex gap-1 bg-gray-100 p-1 rounded-[10px]">
                        <button type="button" @click="periode = 'minggu'" :class="periode === 'minggu' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500'" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition">Minggu Ini</button>
                        <button type="button" @click="periode = 'bulan'" :class="periode === 'bulan' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500'" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition">Bulan Ini</button>
                    </div>
                    <p class="text-xs text-gray-400" x-show="periode === 'minggu'">{{ $labelMinggu }}</p>
                    <p class="text-xs text-gray-400" x-show="periode === 'bulan'" x-cloak>{{ $labelBulan }}</p>
                </div>
                <div class="flex gap-2">
                    <x-button variant="secondary" href="#" x-bind:href="'{{ route('rekap.cabang.export.excel') }}?periode=' + periode">Export Excel</x-button>
                    <x-button variant="secondary" href="#" x-bind:href="'{{ route('rekap.cabang.export.pdf') }}?periode=' + periode">Export PDF</x-button>
                </div>
            </div>

            <div x-show="periode === 'minggu'" class="space-y-4 mt-4">
                @include('rekap.partials.tabel-cabang', ['rekap' => $rekapMingguan, 'stat' => $statMingguan])
            </div>

            <div x-show="periode === 'bulan'" x-cloak class="space-y-4 mt-4">
                @include('rekap.partials.tabel-cabang', ['rekap' => $rekapBulanan, 'stat' => $statBulanan])
            </div>

        </div>

        @if (count($trenCompliance) > 1)
            <x-card>
                <h3 class="font-extrabold text-sm text-gray-800 mb-3">Tren Compliance Antar Periode</h3>
                <div class="h-64">
                    <canvas
                        x-data="{
                            init() {
                                new Chart(this.$el, {
                                    type: 'line',
                                    data: {
                                        labels: @js(collect($trenCompliance)->pluck('periode')),
                                        datasets: [{
                                            label: 'Compliance (%)',
                                            data: @js(collect($trenCompliance)->pluck('persen')),
                                            borderColor: '#307FE2',
                                            backgroundColor: 'rgba(48, 127, 226, .12)',
                                            fill: true,
                                            tension: .3,
                                        }],
                                    },
                                    options: {
                                        maintainAspectRatio: false,
                                        scales: { y: { min: 0, max: 100 } },
                                        plugins: { legend: { display: false } },
                                    },
                                });
                            }
                        }"
                    ></canvas>
                </div>
            </x-card>
        @endif
    </div>
</x-app-layout>
