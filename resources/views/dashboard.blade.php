<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            Dashboard
        </h2>
    </x-slot>

    <div class="p-7 space-y-5">

        {{-- 0. Notifikasi permintaan edit yang menunggu (paling atas, khusus admin) --}}
        @if ($isAdmin && $editRequestsMenunggu->isNotEmpty())
            <div class="bg-yellow-50 border border-yellow-300 rounded-xl px-5 py-3.5 flex items-center justify-between flex-wrap gap-3">
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
            <x-card>
                <div class="w-9 h-9 rounded-lg bg-cakrawala/10 text-cakrawala flex items-center justify-center mb-2.5">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M3 8l9-5 9 5-9 5-9-5zM3 8v8l9 5 9-5V8M12 13v8"></path></svg>
                </div>
                <p class="text-xs text-gray-500 font-semibold mb-1">Total Aset</p>
                <p class="text-3xl font-extrabold text-gray-800">{{ $totalAset }}</p>
            </x-card>
            <x-card>
                <div class="w-9 h-9 rounded-lg bg-nusantara/10 text-nusantara flex items-center justify-center mb-2.5">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M9 12l2 2 4-4M5 6h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"></path></svg>
                </div>
                <p class="text-xs text-gray-500 font-semibold mb-1">Total Form Health Check</p>
                <p class="text-3xl font-extrabold text-gray-800">{{ $totalFormHc }}</p>
            </x-card>
            <x-card>
                <div class="flex items-center gap-3">
                    <div class="rounded-full flex-none flex items-center justify-center" style="background: conic-gradient(#307FE2 {{ $rataCompliance }}%, #e5e7eb 0); width: 52px; height: 52px;">
                        <div class="rounded-full bg-white flex items-center justify-center text-[11px] font-extrabold text-gray-800" style="width: 38px; height: 38px;">{{ $rataCompliance }}%</div>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-semibold mb-1">Rata-rata Compliance</p>
                        <p class="text-base font-extrabold text-gray-800">Seluruh Cabang</p>
                    </div>
                </div>
            </x-card>
        </div>

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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- 2. Ranking cabang paling butuh perhatian (admin saja) --}}
            @if ($isAdmin)
                <x-card>
                    <h3 class="font-extrabold text-sm text-gray-800 mb-3.5">Cabang Paling Butuh Perhatian</h3>
                    @if ($rankingCabang->isEmpty())
                        <p class="text-sm text-gray-400">Belum ada data health check.</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($rankingCabang as $r)
                                <div class="flex items-center justify-between text-sm border-b border-gray-100 pb-2.5">
                                    <div>
                                        <p class="font-semibold text-gray-700">{{ $r['uker'] }} <span class="font-normal text-gray-400 text-xs">({{ $r['kode'] }})</span></p>
                                        <p class="text-gray-400 text-xs">{{ $r['periode'] }} &middot; {{ $r['status_tindak_lanjut'] }}</p>
                                    </div>
                                    <x-badge :color="$r['persen'] >= 95 ? 'green' : ($r['persen'] >= 80 ? 'yellow' : 'red')">
                                        {{ $r['persen'] }}%
                                    </x-badge>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                <x-card>
                    <h3 class="font-extrabold text-sm text-gray-800 mb-1">Uker Belum Mengisi Health Check</h3>
                    <p class="text-xs text-gray-400 mb-3.5">Belum pernah membuat form health check sama sekali (bukan sekadar compliance rendah).</p>
                    @if ($ukerBelumMengisi->isEmpty())
                        <p class="text-sm text-green-600">Semua uker sudah mengisi minimal 1 form health check.</p>
                    @else
                        <div x-data="{ expanded: false }">
                            <div class="flex flex-wrap gap-2">
                                @foreach ($ukerBelumMengisi->take(8) as $u)
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-red-50 text-red-600 border border-red-200">
                                        {{ $u->nama }}
                                    </span>
                                @endforeach
                            </div>
                            @if ($ukerBelumMengisi->count() > 8)
                                <div x-show="expanded" x-cloak class="flex flex-wrap gap-2 mt-2">
                                    @foreach ($ukerBelumMengisi->skip(8) as $u)
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-red-50 text-red-600 border border-red-200">
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
                </x-card>

                <x-card>
                    <h3 class="font-extrabold text-sm text-gray-800 mb-1">Uker Belum Ada Data Aset</h3>
                    <p class="text-xs text-gray-400 mb-3.5">Belum ada satu pun aset tercatat untuk uker ini.</p>
                    @if ($ukerBelumAdaAset->isEmpty())
                        <p class="text-sm text-green-600">Semua uker sudah punya minimal 1 data aset.</p>
                    @else
                        <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto">
                            @foreach ($ukerBelumAdaAset as $u)
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-orange-50 text-orange-600 border border-orange-200">
                                    {{ $u->nama }}
                                </span>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-400 mt-3">{{ $ukerBelumAdaAset->count() }} uker belum ada data aset.</p>
                    @endif
                </x-card>
            @endif

            {{-- 3. Distribusi aset per tipe perangkat --}}
            <x-card>
                <h3 class="font-extrabold text-sm text-gray-800 mb-3.5">Distribusi Aset per Tipe</h3>
                @if ($distribusiPerangkat->isEmpty())
                    <p class="text-sm text-gray-400">Belum ada data aset.</p>
                @else
                    <div class="space-y-3">
                        @php $maxJumlah = $distribusiPerangkat->max('jumlah'); @endphp
                        @foreach ($distribusiPerangkat as $d)
                            <div>
                                <div class="flex justify-between text-xs mb-1.5">
                                    <span class="text-gray-700 font-semibold">{{ $d->perangkat }}</span>
                                    <span class="text-gray-500">{{ $d->jumlah }}</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-nusantara to-cakrawala h-2 rounded-full" style="width: {{ $maxJumlah ? ($d->jumlah / $maxJumlah * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
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
                    <x-button :href="route('uker-tree.index')" class="whitespace-nowrap">
                        Lihat Detail
                    </x-button>
                </div>
            </x-card>
        @endif

        {{-- 4. Aktivitas terbaru --}}
        <x-card>
            <h3 class="font-extrabold text-sm text-gray-800 mb-3.5">Aktivitas Terbaru</h3>
            @if ($aktivitasTerbaru->isEmpty())
                <p class="text-sm text-gray-400">Belum ada aktivitas.</p>
            @else
                <div class="space-y-2.5">
                    @foreach ($aktivitasTerbaru as $a)
                        <div class="flex items-start gap-3 text-sm border-b border-gray-100 pb-2.5">
                            <x-badge :color="$a['jenis'] === 'Aset' ? 'blue' : 'nusantara'" class="flex-none">
                                {{ $a['jenis'] }}
                            </x-badge>
                            <div class="flex-1">
                                <p class="text-gray-700">{{ $a['teks'] }}</p>
                                <p class="text-gray-400 text-xs mt-0.5">{{ $a['waktu']->diffForHumans() }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

    </div>
</x-app-layout>
