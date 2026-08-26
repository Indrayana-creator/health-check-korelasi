<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            Struktur Organisasi
        </h2>
    </x-slot>

    {{-- Connector garis khas org chart (kotak-kotak terhubung), CSS murni --
         teknik display:table klasik biar gak butuh library diagram tambahan.
         Tiap level dalam .org-tree jadi 1 baris tabel, tiap node jadi 1 cell,
         garis penghubung digambar lewat pseudo-element ::before. --}}
    <style>
        .org-tree, .org-tree ul {
            display: table;
            list-style: none;
            margin: 0;
            padding: 0;
            position: relative;
        }
        .org-tree ul {
            width: 100%;
        }
        .org-tree li {
            display: table-cell;
            padding: 1.75em .6em 0;
            vertical-align: top;
            position: relative;
        }
        .org-tree li:before {
            outline: solid 1px #d1d5db;
            content: "";
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
        }
        .org-tree li:first-child:before { left: 50%; }
        .org-tree li:last-child:before { right: 50%; }
        .org-tree li:only-child:before { display: none; }
        .org-tree ul:before {
            outline: solid 1px #d1d5db;
            content: "";
            height: 1.75em;
            left: 50%;
            position: absolute;
            top: -1.75em;
        }
        .org-tree > li {
            padding-top: 0;
        }
        .org-tree > li:before {
            outline: none;
        }
        .org-tree .org-box {
            margin: 0 auto;
        }
    </style>

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

    <div class="p-7 space-y-5 max-w-[1360px]">
        {{-- Tab Rekap Health Check/Aset masih admin-only, jadi non-admin cuma
             lihat tab Struktur Organisasi sendiri -- gak ditawarin link ke
             halaman yang bakal ditolak (403) kalau diklik. --}}
        @if (auth()->user()->role === 'admin')
            <x-page-tabs :tabs="[
                ['label' => 'Rekap Health Check', 'href' => route('rekap.cabang'), 'active' => false],
                ['label' => 'Rekap Aset', 'href' => route('rekap.aset'), 'active' => false],
                ['label' => 'Rekap Permintaan Perangkat', 'href' => route('rekap.permintaanPerangkat'), 'active' => false],
                ['label' => 'Kartu Skor Cabang', 'href' => route('rekap.skorCabang'), 'active' => false],
                ['label' => 'Struktur Organisasi', 'href' => route('uker-tree.index'), 'active' => true],
            ]" />
        @endif

        <x-card>
            <div class="flex items-center justify-between gap-3 flex-wrap mb-1">
                <h3 class="font-extrabold text-sm text-gray-800">Struktur Organisasi</h3>
                <div class="flex gap-2">
                    <x-button variant="secondary" size="sm" :href="route('uker-tree.export.excel')">Export Excel</x-button>
                    <x-button variant="secondary" size="sm" :href="route('uker-tree.export.pdf')">Export PDF</x-button>
                </div>
            </div>
            @if (auth()->user()->role === 'admin')
                <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">
                    Pembagian <strong>Area</strong> di bawah ini masih bersifat draft (dikelompokkan berdasarkan perkiraan
                    geografis), belum dikonfirmasi resmi dari Kanwil. Bisa disesuaikan lagi lewat menu Kelola Uker.
                </div>
            @endif

            @if ($tree)
                <div class="flex flex-wrap items-center gap-3 mb-3 text-[11px] text-gray-500">
                    <span class="font-semibold text-gray-600">Legenda:</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-400"></span> Kanwil</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-400"></span> Area</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-indigo-400"></span> KC</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-teal-400"></span> KCP</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-gray-400"></span> Unit</span>
                    <span class="sm:ml-auto text-gray-400">Klik "Buka" di tiap kotak buat lihat cabang di bawahnya</span>
                </div>

                <x-compliance-legend class="mb-3" />

                <div class="org-tree-scroll overflow-x-auto pb-4">
                    <ul class="org-tree">
                        @include('uker-tree.node', ['node' => $tree, 'level' => 0])
                    </ul>
                </div>
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

                    // Semua segmen di 2 chart ini bisa diklik -- langsung ke Data
                    // Aset dengan filter uker (subtree) + kategori/kondisi yang
                    // diklik, biar chart-nya jadi jalan pintas, bukan cuma angka.
                    this.chartPerangkat = new Chart(this.$refs.canvasPerangkat, {
                        type: 'pie',
                        data: {
                            labels: data.distribusi_perangkat.map(d => d.label),
                            datasets: [{ data: data.distribusi_perangkat.map(d => d.jumlah) }]
                        },
                        options: {
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } },
                            onClick: (evt, elements, chart) => {
                                if (!elements.length) return;
                                const kategori = chart.data.labels[elements[0].index];
                                window.location.href = `{{ route('aset.index') }}?uker_kode=${data.kode}&kategori=${encodeURIComponent(kategori)}`;
                            },
                            onHover: (evt, elements) => { evt.native.target.style.cursor = elements.length ? 'pointer' : 'default'; },
                        }
                    });
                    this.chartKondisi = new Chart(this.$refs.canvasKondisi, {
                        type: 'pie',
                        data: {
                            labels: data.distribusi_kondisi.map(d => d.label),
                            datasets: [{ data: data.distribusi_kondisi.map(d => d.jumlah) }]
                        },
                        options: {
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } },
                            onClick: (evt, elements, chart) => {
                                if (!elements.length) return;
                                const label = chart.data.labels[elements[0].index];
                                const kondisi = label === 'Belum Diisi' ? 'BELUM_DIISI' : label;
                                window.location.href = `{{ route('aset.index') }}?uker_kode=${data.kode}&kondisi=${encodeURIComponent(kondisi)}`;
                            },
                            onHover: (evt, elements) => { evt.native.target.style.cursor = elements.length ? 'pointer' : 'default'; },
                        }
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
                                {{-- "Belum Diisi" = kolom Kondisi aset itu masih kosong (data lama,
                                     sebelum kolom ini wajib diisi) -- kasih jalan pintas langsung ke
                                     daftar asetnya, bukan cuma angka doang tanpa tindak lanjut. --}}
                                <template x-if="($store.ukerDetail.data.distribusi_kondisi.find(d => d.label === 'Belum Diisi')?.jumlah ?? 0) > 0">
                                    <p class="text-xs text-gray-500 text-center mt-2">
                                        <span x-text="$store.ukerDetail.data.distribusi_kondisi.find(d => d.label === 'Belum Diisi').jumlah"></span>
                                        aset belum diisi kolom Kondisinya &mdash;
                                        <a :href="`{{ route('aset.index') }}?uker_kode=${$store.ukerDetail.data.kode}&kondisi=BELUM_DIISI`" class="text-cakrawala font-semibold hover:underline">lihat &amp; lengkapi</a>
                                    </p>
                                </template>
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
                            plugins: { legend: { display: false } },
                            // Klik kategori -- langsung ke Monitoring Kendala yang
                            // difilter uker (subtree) + kategori itu, buat lihat item
                            // Not OK spesifik yang bikin compliance-nya gak 100%.
                            onClick: (evt, elements, chart) => {
                                if (!elements.length) return;
                                const kategori = chart.data.labels[elements[0].index];
                                window.location.href = `{{ route('monitoring.index') }}?uker_kode=${data.kode}&kategori=${encodeURIComponent(kategori)}`;
                            },
                            onHover: (evt, elements) => { evt.native.target.style.cursor = elements.length ? 'pointer' : 'default'; },
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
