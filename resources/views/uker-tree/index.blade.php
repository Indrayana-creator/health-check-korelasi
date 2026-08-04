<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            Struktur Organisasi
        </h2>
    </x-slot>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('ukerDetail', {
                open: false,
                loading: false,
                data: null,
                async buka(kode) {
                    this.open = true;
                    this.loading = true;
                    this.data = null;
                    try {
                        const res = await fetch(`/uker-tree/${kode}/detail`);
                        this.data = await res.json();
                    } catch (e) {
                        this.data = { error: true };
                    }
                    this.loading = false;
                },
                tutup() {
                    this.open = false;
                }
            });

            Alpine.store('complianceDetail', {
                open: false,
                loading: false,
                data: null,
                async buka(kode) {
                    this.open = true;
                    this.loading = true;
                    this.data = null;
                    try {
                        const res = await fetch(`/uker-tree/${kode}/compliance-detail`);
                        this.data = await res.json();
                    } catch (e) {
                        this.data = { error: true };
                    }
                    this.loading = false;
                },
                tutup() {
                    this.open = false;
                }
            });
        });
    </script>

    <div class="p-7 space-y-5">
        <x-card>
            <h3 class="font-extrabold text-sm text-gray-800 mb-1">Struktur Organisasi</h3>
            <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">
                Pembagian <strong>Area</strong> di bawah ini masih bersifat draft (dikelompokkan berdasarkan perkiraan
                geografis), belum dikonfirmasi resmi dari Kanwil. Bisa disesuaikan lagi lewat menu Kelola Uker.
            </div>

            @if ($tree)
                @include('uker-tree.node', ['node' => $tree, 'level' => 0])
            @else
                <p class="text-gray-500 text-sm">Data struktur belum tersedia.</p>
            @endif
        </x-card>
    </div>

    {{-- Modal detail rekap + pie chart (Data Aset) --}}
    <div
        x-data="{
            chartPerangkat: null,
            chartKondisi: null,
            renderCharts(data) {
                this.$nextTick(() => {
                    if (this.chartPerangkat) { this.chartPerangkat.destroy(); this.chartPerangkat = null; }
                    if (this.chartKondisi) { this.chartKondisi.destroy(); this.chartKondisi = null; }
                    if (!data || data.error || !this.$refs.canvasPerangkat) return;

                    this.chartPerangkat = new Chart(this.$refs.canvasPerangkat, {
                        type: 'pie',
                        data: {
                            labels: data.distribusi_perangkat.map(d => d.label),
                            datasets: [{ data: data.distribusi_perangkat.map(d => d.jumlah) }]
                        },
                        options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } } }
                    });
                    this.chartKondisi = new Chart(this.$refs.canvasKondisi, {
                        type: 'pie',
                        data: {
                            labels: data.distribusi_kondisi.map(d => d.label),
                            datasets: [{ data: data.distribusi_kondisi.map(d => d.jumlah) }]
                        },
                        options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } } }
                    });
                });
            }
        }"
        x-effect="renderCharts($store.ukerDetail.data)"
    >
        <div x-show="$store.ukerDetail.open" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="$store.ukerDetail.tutup()">
            <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="font-extrabold text-lg text-gray-800" x-text="$store.ukerDetail.data?.nama ?? 'Memuat...'"></h3>
                    <button @click="$store.ukerDetail.tutup()" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                </div>

                <template x-if="$store.ukerDetail.loading">
                    <p class="text-gray-400 text-sm py-8 text-center">Memuat data...</p>
                </template>

                <template x-if="!$store.ukerDetail.loading && $store.ukerDetail.data?.error">
                    <p class="text-red-500 text-sm py-8 text-center">Gagal memuat data.</p>
                </template>

                <template x-if="!$store.ukerDetail.loading && $store.ukerDetail.data && !$store.ukerDetail.data.error">
                    <div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6 text-sm">
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-gray-400 text-xs">Total Aset</p>
                                <p class="font-extrabold text-gray-800 text-lg" x-text="$store.ukerDetail.data.total_aset"></p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-gray-400 text-xs">Rata Compliance</p>
                                <p class="font-extrabold text-gray-800 text-lg" x-text="$store.ukerDetail.data.rata_compliance !== null ? $store.ukerDetail.data.rata_compliance + '%' : '-'"></p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-gray-400 text-xs">Form Health Check</p>
                                <p class="font-extrabold text-gray-800 text-lg" x-text="$store.ukerDetail.data.jumlah_form_hc"></p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-gray-400 text-xs">Unit Ada Data Aset</p>
                                <p class="font-extrabold text-gray-800 text-lg" x-text="$store.ukerDetail.data.jumlah_unit_ada_aset + ' / ' + $store.ukerDetail.data.jumlah_unit_total"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm font-medium text-gray-700 mb-2 text-center">Distribusi per Kategori</p>
                                <canvas x-ref="canvasPerangkat"></canvas>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700 mb-2 text-center">Distribusi Kondisi</p>
                                <canvas x-ref="canvasKondisi"></canvas>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Modal kedua: fokus Health Check/Compliance --}}
    <div
        x-data="{
            chartKategori: null,
            renderChart(data) {
                this.$nextTick(() => {
                    if (this.chartKategori) { this.chartKategori.destroy(); this.chartKategori = null; }
                    if (!data || data.error || !this.$refs.canvasKategori) return;

                    this.chartKategori = new Chart(this.$refs.canvasKategori, {
                        type: 'bar',
                        data: {
                            labels: data.per_kategori.map(d => d.label),
                            datasets: [{ label: 'Compliance (%)', data: data.per_kategori.map(d => d.persen), backgroundColor: '#307FE2' }]
                        },
                        options: {
                            indexAxis: 'y',
                            scales: { x: { min: 0, max: 100 } },
                            plugins: { legend: { display: false } }
                        }
                    });
                });
            }
        }"
        x-effect="renderChart($store.complianceDetail.data)"
    >
        <div x-show="$store.complianceDetail.open" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="$store.complianceDetail.tutup()">
            <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="font-extrabold text-lg text-gray-800" x-text="$store.complianceDetail.data?.nama ?? 'Memuat...'"></h3>
                        <p class="text-xs text-gray-400">Rekap Health Check</p>
                    </div>
                    <button @click="$store.complianceDetail.tutup()" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                </div>

                <template x-if="$store.complianceDetail.loading">
                    <p class="text-gray-400 text-sm py-8 text-center">Memuat data...</p>
                </template>

                <template x-if="!$store.complianceDetail.loading && $store.complianceDetail.data?.error">
                    <p class="text-red-500 text-sm py-8 text-center">Gagal memuat data.</p>
                </template>

                <template x-if="!$store.complianceDetail.loading && $store.complianceDetail.data && !$store.complianceDetail.data.error">
                    <div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6 text-sm">
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-gray-400 text-xs">Jumlah Form</p>
                                <p class="font-extrabold text-gray-800 text-lg" x-text="$store.complianceDetail.data.jumlah_form"></p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-gray-400 text-xs">Rata Compliance</p>
                                <p class="font-extrabold text-gray-800 text-lg" x-text="$store.complianceDetail.data.rata_compliance !== null ? $store.complianceDetail.data.rata_compliance + '%' : '-'"></p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-gray-400 text-xs">Unit Ada Form</p>
                                <p class="font-extrabold text-gray-800 text-lg" x-text="$store.complianceDetail.data.jumlah_unit_ada_form + ' / ' + $store.complianceDetail.data.jumlah_unit_total"></p>
                            </div>
                        </div>

                        <p class="text-sm font-medium text-gray-700 mb-2">Compliance per Kategori Checklist</p>
                        <div class="mb-6">
                            <canvas x-ref="canvasKategori"></canvas>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                            <div>
                                <p class="font-medium text-gray-700 mb-2">Status Approval</p>
                                <template x-for="s in $store.complianceDetail.data.status_approval" :key="s.label">
                                    <div class="flex justify-between border-b py-1">
                                        <span x-text="s.label"></span>
                                        <span class="font-semibold" x-text="s.jumlah"></span>
                                    </div>
                                </template>
                            </div>
                            <div>
                                <p class="font-medium text-gray-700 mb-2">Status Tindak Lanjut</p>
                                <template x-for="s in $store.complianceDetail.data.status_tindak_lanjut" :key="s.label">
                                    <div class="flex justify-between border-b py-1">
                                        <span x-text="s.label"></span>
                                        <span class="font-semibold" x-text="s.jumlah"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
