<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard
        </h2>
    </x-slot>

    @if ($isAdmin)
        {{-- Dipindah dari halaman /uker-tree -- dipakai buat modal detail & chart di section Struktur Organisasi --}}
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
    @endif

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 0. Notifikasi permintaan edit yang menunggu (paling atas, khusus admin) --}}
            @if ($isAdmin && $editRequestsMenunggu->isNotEmpty())
                <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <p class="font-semibold text-yellow-800">{{ $editRequestsMenunggu->count() }} permintaan edit aset menunggu approval</p>
                            <p class="text-sm text-yellow-700">
                                @foreach ($editRequestsMenunggu->take(3) as $r)
                                    {{ $r->aset->uker?->nama }} ({{ $r->requester?->name }}){{ !$loop->last ? ', ' : '' }}
                                @endforeach
                                @if ($editRequestsMenunggu->count() > 3) , dan lainnya @endif
                            </p>
                        </div>
                        <a href="{{ route('aset.editRequests.index') }}" class="bg-yellow-600 text-white px-4 py-2 rounded text-sm hover:bg-yellow-700 whitespace-nowrap">Lihat &amp; Proses</a>
                    </div>
                </div>
            @endif

            {{-- 1. KPI Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-indigo-500">
                    <p class="text-sm text-gray-500">Total Aset</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalAset }}</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-blue-500">
                    <p class="text-sm text-gray-500">Total Form Health Check</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalFormHc }}</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border-t-4 {{ $rataCompliance >= 80 ? 'border-green-500' : 'border-red-500' }}">
                    <p class="text-sm text-gray-500">Rata-rata Compliance</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $rataCompliance }}%</p>
                </div>
            </div>

            {{-- Notifikasi status permintaan edit aset milik sendiri (khusus user, bukan admin) --}}
            @if (!$isAdmin && $editRequestsSaya->isNotEmpty())
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="font-semibold text-gray-800 mb-4">Status Permintaan Edit Aset Saya</h3>
                    <div class="space-y-3">
                        @foreach ($editRequestsSaya as $r)
                            <div class="flex items-start justify-between gap-3 text-sm border-b pb-2">
                                <div>
                                    <p class="font-medium text-gray-700 font-mono text-xs">{{ $r->aset->no_asset }}</p>
                                    @if ($r->status === 'Ditolak' && $r->catatan_admin)
                                        <p class="text-red-600 text-xs mt-1">Alasan ditolak: {{ $r->catatan_admin }}</p>
                                    @elseif ($r->status === 'Disetujui')
                                        <p class="text-green-600 text-xs mt-1">Disetujui, silakan edit sekarang (hanya berlaku 1x edit).</p>
                                    @endif
                                </div>
                                <span class="px-2 py-1 text-xs rounded font-semibold whitespace-nowrap
                                    {{ match($r->status) {
                                        'Disetujui' => 'bg-green-100 text-green-700',
                                        'Menunggu' => 'bg-yellow-100 text-yellow-700',
                                        'Ditolak' => 'bg-red-100 text-red-700',
                                        default => 'bg-gray-100 text-gray-600',
                                    } }}">
                                    {{ $r->status }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- 2. Ranking cabang paling butuh perhatian (admin saja) --}}
                @if ($isAdmin)
                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <h3 class="font-semibold text-gray-800 mb-4">Cabang Paling Butuh Perhatian</h3>
                        @if ($rankingCabang->isEmpty())
                            <p class="text-sm text-gray-400">Belum ada data health check.</p>
                        @else
                            <div class="space-y-2">
                                @foreach ($rankingCabang as $r)
                                    <div class="flex items-center justify-between text-sm border-b pb-2">
                                        <div>
                                            <p class="font-medium text-gray-700">{{ $r['uker'] }}</p>
                                            <p class="text-gray-400 text-xs">{{ $r['periode'] }} &middot; {{ $r['status_tindak_lanjut'] }}</p>
                                        </div>
                                        <span class="px-2 py-1 text-xs rounded font-semibold
                                            {{ $r['persen'] >= 95 ? 'bg-green-100 text-green-700' : ($r['persen'] >= 80 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                            {{ $r['persen'] }}%
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <h3 class="font-semibold text-gray-800 mb-1">Uker Belum Mengisi Health Check</h3>
                        <p class="text-xs text-gray-400 mb-4">Belum pernah membuat form health check sama sekali (bukan sekadar compliance rendah).</p>
                        @if ($ukerBelumMengisi->isEmpty())
                            <p class="text-sm text-green-600">Semua uker sudah mengisi minimal 1 form health check.</p>
                        @else
                            <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto">
                                @foreach ($ukerBelumMengisi as $u)
                                    <span class="px-2 py-1 text-xs rounded bg-red-50 text-red-600 border border-red-200">
                                        {{ $u->nama }}
                                    </span>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-400 mt-3">{{ $ukerBelumMengisi->count() }} uker belum mengisi.</p>
                        @endif
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <h3 class="font-semibold text-gray-800 mb-1">Uker Belum Ada Data Aset</h3>
                        <p class="text-xs text-gray-400 mb-4">Belum ada satu pun aset tercatat untuk uker ini.</p>
                        @if ($ukerBelumAdaAset->isEmpty())
                            <p class="text-sm text-green-600">Semua uker sudah punya minimal 1 data aset.</p>
                        @else
                            <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto">
                                @foreach ($ukerBelumAdaAset as $u)
                                    <span class="px-2 py-1 text-xs rounded bg-orange-50 text-orange-600 border border-orange-200">
                                        {{ $u->nama }}
                                    </span>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-400 mt-3">{{ $ukerBelumAdaAset->count() }} uker belum ada data aset.</p>
                        @endif
                    </div>
                @endif

                {{-- 3. Distribusi aset per tipe perangkat --}}
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="font-semibold text-gray-800 mb-4">Distribusi Aset per Tipe</h3>
                    @if ($distribusiPerangkat->isEmpty())
                        <p class="text-sm text-gray-400">Belum ada data aset.</p>
                    @else
                        <div class="space-y-2">
                            @php $maxJumlah = $distribusiPerangkat->max('jumlah'); @endphp
                            @foreach ($distribusiPerangkat as $d)
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-700">{{ $d->perangkat }}</span>
                                        <span class="text-gray-500">{{ $d->jumlah }}</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2">
                                        <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $maxJumlah ? ($d->jumlah / $maxJumlah * 100) : 0 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- 5. Struktur Organisasi (Tree) -- khusus admin, dipindah dari halaman /uker-tree --}}
            @if ($isAdmin)
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="font-semibold text-gray-800 mb-1">Struktur Organisasi</h3>
                    <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
                        Pembagian <strong>Area</strong> di bawah ini masih bersifat draft (dikelompokkan berdasarkan perkiraan
                        geografis), belum dikonfirmasi resmi dari Kanwil. Bisa disesuaikan lagi lewat menu Kelola Uker.
                    </div>

                    @if ($tree)
                        @include('uker-tree.node', ['node' => $tree, 'level' => 0])
                    @else
                        <p class="text-gray-500 text-sm">Data struktur belum tersedia.</p>
                    @endif
                </div>
            @endif

            {{-- 4. Aktivitas terbaru --}}
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="font-semibold text-gray-800 mb-4">Aktivitas Terbaru</h3>
                @if ($aktivitasTerbaru->isEmpty())
                    <p class="text-sm text-gray-400">Belum ada aktivitas.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($aktivitasTerbaru as $a)
                            <div class="flex items-start gap-3 text-sm border-b pb-2">
                                <span class="px-2 py-0.5 text-xs rounded {{ $a['jenis'] === 'Aset' ? 'bg-indigo-100 text-indigo-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ $a['jenis'] }}
                                </span>
                                <div class="flex-1">
                                    <p class="text-gray-700">{{ $a['teks'] }}</p>
                                    <p class="text-gray-400 text-xs">{{ $a['waktu']->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>

    @if ($isAdmin)
        {{-- Modal detail rekap + pie chart (Data Aset), dipindah dari halaman /uker-tree --}}
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
                <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="font-semibold text-lg text-gray-800" x-text="$store.ukerDetail.data?.nama ?? 'Memuat...'"></h3>
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
                                <div class="bg-gray-50 p-3 rounded">
                                    <p class="text-gray-400 text-xs">Total Aset</p>
                                    <p class="font-bold text-gray-800 text-lg" x-text="$store.ukerDetail.data.total_aset"></p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded">
                                    <p class="text-gray-400 text-xs">Rata Compliance</p>
                                    <p class="font-bold text-gray-800 text-lg" x-text="$store.ukerDetail.data.rata_compliance !== null ? $store.ukerDetail.data.rata_compliance + '%' : '-'"></p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded">
                                    <p class="text-gray-400 text-xs">Form Health Check</p>
                                    <p class="font-bold text-gray-800 text-lg" x-text="$store.ukerDetail.data.jumlah_form_hc"></p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded">
                                    <p class="text-gray-400 text-xs">Unit Ada Data Aset</p>
                                    <p class="font-bold text-gray-800 text-lg" x-text="$store.ukerDetail.data.jumlah_unit_ada_aset + ' / ' + $store.ukerDetail.data.jumlah_unit_total"></p>
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

        {{-- Modal kedua: fokus Health Check/Compliance, dipindah dari halaman /uker-tree --}}
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
                                datasets: [{ label: 'Compliance (%)', data: data.per_kategori.map(d => d.persen), backgroundColor: '#6366f1' }]
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
                <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-semibold text-lg text-gray-800" x-text="$store.complianceDetail.data?.nama ?? 'Memuat...'"></h3>
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
                                <div class="bg-gray-50 p-3 rounded">
                                    <p class="text-gray-400 text-xs">Jumlah Form</p>
                                    <p class="font-bold text-gray-800 text-lg" x-text="$store.complianceDetail.data.jumlah_form"></p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded">
                                    <p class="text-gray-400 text-xs">Rata Compliance</p>
                                    <p class="font-bold text-gray-800 text-lg" x-text="$store.complianceDetail.data.rata_compliance !== null ? $store.complianceDetail.data.rata_compliance + '%' : '-'"></p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded">
                                    <p class="text-gray-400 text-xs">Unit Ada Form</p>
                                    <p class="font-bold text-gray-800 text-lg" x-text="$store.complianceDetail.data.jumlah_unit_ada_form + ' / ' + $store.complianceDetail.data.jumlah_unit_total"></p>
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
    @endif
</x-app-layout>
