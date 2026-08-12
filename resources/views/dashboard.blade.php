<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            Dashboard
        </h2>
        <p class="text-xs text-gray-500 mt-0.5">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</p>
    </x-slot>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

    <div class="p-7 space-y-4 max-w-[1360px]">

        {{-- 0. Notifikasi permintaan edit yang menunggu (paling atas, khusus admin) --}}
        @if ($isAdmin && $editRequestsMenunggu->isNotEmpty())
            <div class="bg-yellow-50 border border-yellow-300 rounded-2xl px-5 py-3.5 flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 flex-none text-yellow-600"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                    <div>
                        <p class="font-semibold text-sm text-yellow-800">{{ $editRequestsMenunggu->count() }} permintaan edit aset menunggu approval</p>
                        <p class="text-xs text-yellow-700 mt-0.5">
                            @foreach ($editRequestsMenunggu->take(3) as $r)
                                {{ $r->aset->uker?->nama }} ({{ $r->requester?->name }}){{ !$loop->last ? ', ' : '' }}
                            @endforeach
                            @if ($editRequestsMenunggu->count() > 3) , dan lainnya @endif
                        </p>
                    </div>
                </div>
                <x-button variant="warning" :href="route('aset.editRequests.index')" class="whitespace-nowrap">Lihat &amp; Proses</x-button>
            </div>
        @endif

        {{-- 1. KPI Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-cakrawala/10 text-cakrawala flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M3 8l9-5 9 5-9 5-9-5zM3 8v8l9 5 9-5V8M12 13v8"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Total Aset</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ $totalAset }}</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-nusantara/10 text-nusantara flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M9 12l2 2 4-4M5 6h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Total Form Health Check</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ $totalFormHc }}</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-green-100 text-green-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M9 12l2 2 4-4M12 22a10 10 0 100-20 10 10 0 000 20z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Rata-rata Compliance</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ $rataCompliance }}%</p>
            </x-card>
        </div>

        {{-- 1b. Kesehatan Checklist per Kategori -- dari form TERBARU tiap uker
             dalam cakupan RBAC (admin: semua, user: sendiri + turunan), bukan
             dari seluruh histori. Kategori A-D pakai ComplianceScale yang sama
             kayak tempat lain; Kategori E (Dokumentasi Visual) terpisah, gak
             ikut compliance manapun. --}}
        <x-card>
            <h3 class="font-extrabold text-sm text-gray-800 mb-1">Kesehatan Checklist per Kategori</h3>
            <p class="text-xs text-gray-400 mb-3">Dari form Health Check terbaru tiap uker (bukan seluruh histori), sesuai cakupan akses Anda.</p>

            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <x-compliance-legend />
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs">
                    <span class="font-semibold text-gray-500">Status item:</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full" style="background:#22C55E"></span> OK</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full" style="background:#EF4444"></span> Not OK</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full" style="background:#F59E0B"></span> N/A</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full" style="background:#94A3B8"></span> Belum Diperiksa</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
                @foreach ($kesehatanPerKategori as $k)
                    <div class="border border-gray-200 rounded-xl p-4">
                        <p class="text-xs font-bold text-gray-700 mb-2 leading-snug">{{ $k['kategori'] }}</p>
                        @if (array_sum($k['breakdown']) > 0)
                            <div class="h-32 mb-2">
                                <canvas
                                    x-data="{
                                        init() {
                                            new Chart(this.$el, {
                                                type: 'doughnut',
                                                data: {
                                                    labels: @js(array_keys($k['breakdown'])),
                                                    datasets: [{
                                                        data: @js(array_values($k['breakdown'])),
                                                        backgroundColor: ['#22C55E', '#EF4444', '#F59E0B', '#94A3B8'],
                                                        borderWidth: 0,
                                                    }],
                                                },
                                                options: {
                                                    maintainAspectRatio: false,
                                                    plugins: { legend: { display: false } },
                                                },
                                            });
                                        }
                                    }"
                                ></canvas>
                            </div>
                            <div class="flex items-center justify-between">
                                <x-badge :color="$k['warna']">{{ $k['label'] }}</x-badge>
                                <span class="text-sm font-extrabold text-gray-800">{{ $k['persen'] }}%</span>
                            </div>
                        @else
                            <p class="text-xs text-gray-400 h-32 flex items-center justify-center text-center">Belum ada data.</p>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Kategori E -- bentuk beda sengaja, cuma link foto (bukan
                 checklist OK/Not OK), jadi TIDAK masuk compliance manapun. --}}
            <div class="border-t border-gray-100 pt-4">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <p class="text-xs font-bold text-gray-700 mb-1">E - Dokumentasi Visual</p>
                        <p class="text-xs text-gray-400">Link foto bukti pemeriksaan -- bukan checklist, gak ikut hitungan compliance.</p>
                    </div>
                    @if ($totalFormTerbaru > 0)
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 flex-none">
                                <canvas
                                    x-data="{
                                        init() {
                                            new Chart(this.$el, {
                                                type: 'doughnut',
                                                data: {
                                                    labels: ['Lengkap 3/3', 'Belum Lengkap'],
                                                    datasets: [{
                                                        data: @js([$formLengkapDokumentasi, $totalFormTerbaru - $formLengkapDokumentasi]),
                                                        backgroundColor: ['#22C55E', '#E5E7EB'],
                                                        borderWidth: 0,
                                                    }],
                                                },
                                                options: {
                                                    maintainAspectRatio: false,
                                                    plugins: { legend: { display: false } },
                                                },
                                            });
                                        }
                                    }"
                                ></canvas>
                            </div>
                            <p class="text-sm font-extrabold text-gray-800 whitespace-nowrap">{{ $formLengkapDokumentasi }} dari {{ $totalFormTerbaru }} form sudah lengkap 3/3 foto</p>
                        </div>
                    @else
                        <p class="text-xs text-gray-400">Belum ada data.</p>
                    @endif
                </div>
            </div>
        </x-card>

        {{-- Notifikasi status permintaan edit aset milik sendiri (khusus user, bukan admin) --}}
        @if (!$isAdmin && $editRequestsSaya->isNotEmpty())
            <x-card>
                <h3 class="font-extrabold text-sm text-gray-800 mb-3.5">Status Permintaan Edit Aset Saya</h3>
                <div class="space-y-2.5">
                    @foreach ($editRequestsSaya as $r)
                        <div class="flex items-start justify-between gap-3 text-sm border-b border-gray-100 pb-2.5">
                            <div>
                                <p class="font-semibold text-gray-700 font-mono text-xs">{{ $r->aset->no_asset }}</p>
                                @if ($r->status === 'Ditolak' && $r->catatan_admin)
                                    <p class="text-red-600 text-xs mt-1">Alasan ditolak: {{ $r->catatan_admin }}</p>
                                @elseif ($r->status === 'Disetujui')
                                    <p class="text-green-600 text-xs mt-1">Disetujui, silakan edit sekarang (hanya berlaku 1x edit).</p>
                                @endif
                            </div>
                            <x-badge :color="match($r->status) { 'Disetujui' => 'green', 'Menunggu' => 'yellow', 'Ditolak' => 'red', default => 'gray' }">
                                {{ $r->status }}
                            </x-badge>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-[1.4fr_1fr] gap-4 items-start">

            {{-- 2. Panel "butuh perhatian" bertab (admin) / Distribusi Aset (non-admin) --}}
            <x-card padding="p-0" x-data="{ tab: '{{ $isAdmin ? 'ranking' : 'belum-isi' }}' }">
                    <div class="flex items-center gap-1.5 px-5 pt-4">
                        <div class="flex gap-1 bg-gray-100 p-1 rounded-[10px]">
                            @if ($isAdmin)
                                <button type="button" @click="tab = 'ranking'" :class="tab === 'ranking' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500'" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition">Ranking Terendah</button>
                            @endif
                            <button type="button" @click="tab = 'belum-isi'" :class="tab === 'belum-isi' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500'" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition">Belum Isi HC</button>
                            <button type="button" @click="tab = 'belum-aset'" :class="tab === 'belum-aset' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500'" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition">Belum Ada Aset</button>
                        </div>
                    </div>

                    <div class="p-5">
                        @if ($isAdmin)
                            <div x-show="tab === 'ranking'">
                                @if ($rankingCabang->isEmpty())
                                    <p class="text-sm text-gray-400">Belum ada data health check.</p>
                                @else
                                    <x-compliance-legend class="mb-3" />
                                    <div class="flex flex-col">
                                        @foreach ($rankingCabang as $r)
                                            <div class="flex items-center justify-between text-sm border-b border-gray-100 py-2.5">
                                                <div>
                                                    <p class="font-semibold text-gray-700">{{ $r['uker'] }} <span class="font-normal text-gray-400 text-xs">({{ $r['kode'] }})</span></p>
                                                    <p class="text-gray-400 text-xs">{{ $r['periode'] }} &middot; {{ $r['status_tindak_lanjut'] }}</p>
                                                </div>
                                                <x-badge :color="\App\Support\ComplianceScale::badgeColor($r['persen'])">
                                                    {{ $r['persen'] }}%
                                                </x-badge>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div x-show="tab === 'belum-isi'" x-cloak>
                            <p class="text-xs text-gray-500 mb-3">Belum pernah membuat form health check sama sekali (bukan sekadar compliance rendah).</p>
                            @if ($ukerBelumMengisi->isEmpty())
                                <p class="text-sm text-green-600">Semua uker sudah mengisi minimal 1 form health check.</p>
                            @else
                                <div x-data="{ expanded: false }">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($ukerBelumMengisi->take(8) as $u)
                                            <span class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-50 text-red-600 border border-red-200">
                                                {{ $u->nama }}
                                            </span>
                                        @endforeach
                                    </div>
                                    @if ($ukerBelumMengisi->count() > 8)
                                        <div x-show="expanded" x-cloak class="flex flex-wrap gap-2 mt-2">
                                            @foreach ($ukerBelumMengisi->skip(8) as $u)
                                                <span class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-50 text-red-600 border border-red-200">
                                                    {{ $u->nama }}
                                                </span>
                                            @endforeach
                                        </div>
                                        <button type="button" @click="expanded = !expanded" class="mt-3 text-xs font-semibold text-cakrawala hover:underline">
                                            <span x-show="!expanded">dan {{ $ukerBelumMengisi->count() - 8 }} lainnya &rarr; Lihat semua</span>
                                            <span x-show="expanded" x-cloak>Sembunyikan</span>
                                        </button>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-400 mt-3">{{ $ukerBelumMengisi->count() }} uker belum mengisi.</p>
                            @endif
                        </div>

                        <div x-show="tab === 'belum-aset'" x-cloak>
                            <p class="text-xs text-gray-500 mb-3">Belum ada satu pun aset tercatat untuk uker ini.</p>
                            @if ($ukerBelumAdaAset->isEmpty())
                                <p class="text-sm text-green-600">Semua uker sudah punya minimal 1 data aset.</p>
                            @else
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($ukerBelumAdaAset as $u)
                                        <span class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-orange-50 text-orange-600 border border-orange-200">
                                            {{ $u->nama }}
                                        </span>
                                    @endforeach
                                </div>
                                <p class="text-xs text-gray-400 mt-3">{{ $ukerBelumAdaAset->count() }} uker belum ada data aset.</p>
                            @endif
                        </div>
                    </div>
                </x-card>

            <div class="flex flex-col gap-4">
                {{-- 3. Distribusi aset per tipe perangkat --}}
                <x-card>
                    <h3 class="font-extrabold text-sm text-gray-800 mb-4">Distribusi Aset per Tipe</h3>
                    @if ($distribusiPerangkat->isEmpty())
                        <p class="text-sm text-gray-400">Belum ada data aset.</p>
                    @else
                        <div class="space-y-3.5">
                            @php $maxJumlah = $distribusiPerangkat->max('jumlah'); @endphp
                            @foreach ($distribusiPerangkat as $d)
                                <div>
                                    <div class="flex justify-between text-xs mb-1.5">
                                        <span class="text-gray-700 font-semibold">{{ $d->perangkat }}</span>
                                        <span class="text-gray-500">{{ $d->jumlah }}</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-[7px]">
                                        <div class="bg-gradient-to-r from-nusantara to-cakrawala h-[7px] rounded-full" style="width: {{ $maxJumlah ? ($d->jumlah / $maxJumlah * 100) : 0 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                {{-- 4. Aktivitas terbaru --}}
                <x-card>
                    <h3 class="font-extrabold text-sm text-gray-800 mb-3.5">Aktivitas Terbaru</h3>
                    @if ($aktivitasTerbaru->isEmpty())
                        <p class="text-sm text-gray-400">Belum ada aktivitas.</p>
                    @else
                        <div class="flex flex-col">
                            @foreach ($aktivitasTerbaru as $a)
                                <div class="flex items-start gap-2.5 text-sm border-b border-gray-100 py-2.5">
                                    <x-badge :color="$a['jenis'] === 'Aset' ? 'blue' : 'nusantara'" class="flex-none">
                                        {{ $a['jenis'] }}
                                    </x-badge>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-gray-700 text-[12.5px]">{{ $a['teks'] }}</p>
                                        <p class="text-gray-400 text-xs mt-0.5">{{ $a['waktu']->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>
        </div>

        {{-- 5. Struktur Organisasi -- ringkasan kecil, khusus admin. Detail lengkap
             (tree 366 unit) ada di halaman tersendiri /uker-tree, biar dashboard
             gak kepanjangan. --}}
        @if ($isAdmin)
            <x-card>
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        <h3 class="font-extrabold text-sm text-gray-800 mb-1">Struktur Organisasi</h3>
                        @if ($tree)
                            <p class="text-sm text-gray-500">
                                {{ $tree['jumlah_unit_bawah'] }} unit di bawah {{ $tree['nama'] }}, rata-rata compliance
                                {{ $tree['rata_compliance'] !== null ? $tree['rata_compliance'].'%' : '-' }}.
                            </p>
                        @else
                            <p class="text-sm text-gray-400">Data struktur belum tersedia.</p>
                        @endif
                    </div>
                    <x-button variant="secondary" :href="route('uker-tree.index')" class="whitespace-nowrap">
                        Lihat Detail
                    </x-button>
                </div>
            </x-card>

            {{-- 6. Kendala Aktif -- ringkasan kecil, link ke Monitoring Kendala --}}
            <x-card :class="$totalKendalaAktif > 0 ? '!border-red-200' : ''">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg {{ $totalKendalaAktif > 0 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600' }} flex items-center justify-center flex-none">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm text-gray-800 mb-1">Kendala Aktif</h3>
                            <p class="text-sm text-gray-500">
                                @if ($totalKendalaAktif > 0)
                                    {{ $totalKendalaAktif }} item checklist "Not OK" masih belum selesai ditindaklanjuti.
                                @else
                                    Semua item checklist "Not OK" sudah selesai ditindaklanjuti.
                                @endif
                            </p>
                        </div>
                    </div>
                    <x-button variant="secondary" :href="route('monitoring.index')" class="whitespace-nowrap">
                        Lihat Detail
                    </x-button>
                </div>
            </x-card>
        @endif

    </div>
</x-app-layout>
