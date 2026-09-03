<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            Data Aset
        </h2>
        <p class="text-xs text-gray-500 mt-0.5">{{ $asetList->total() }} dari {{ $totalKeseluruhan }} aset ditampilkan</p>
    </x-slot>

    <div class="p-7 space-y-4 max-w-[1360px]" x-data="{ density: 'comfortable' }">

        <x-flash-status />

        <div class="flex flex-wrap gap-2 items-center">
            <x-button :href="route('aset.create')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 5v14M5 12h14"></path></svg>
                Tambah Aset
            </x-button>
            <x-dropdown align="left" width="48">
                <x-slot name="trigger">
                    <button type="button" class="flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-200 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50">
                        Kelola Massal
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><path d="M6 9l6 6 6-6"></path></svg>
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link :href="route('aset.bulkUploadForm')">Upload Massal (Excel)</x-dropdown-link>
                    <x-dropdown-link :href="route('aset.bulkDeleteForm')" class="!text-red-600">Delete Massal (Excel)</x-dropdown-link>
                </x-slot>
            </x-dropdown>
            <x-dropdown align="left" width="48">
                <x-slot name="trigger">
                    <button type="button" class="flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-200 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50">
                        Export
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><path d="M6 9l6 6 6-6"></path></svg>
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link :href="route('aset.export.excel', request()->query())">Excel (.xlsx)</x-dropdown-link>
                    <x-dropdown-link :href="route('aset.export.pdf', request()->query())">PDF</x-dropdown-link>
                    @if (request('uker_kode'))
                        <x-dropdown-link :href="route('aset.qrSheet', request()->query())">Cetak QR Massal (PDF)</x-dropdown-link>
                    @endif
                </x-slot>
            </x-dropdown>
            <x-button variant="secondary" :href="route('aset.trash')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"></path></svg>
                Sampah
            </x-button>
        </div>

        {{-- Stat cards: gambaran besar sesuai scope user, gak ikut filter q/uker_kode/kondisi di bawah --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-cakrawala/10 text-cakrawala flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M3 8l9-5 9 5-9 5-9-5zM3 8v8l9 5 9-5V8M12 13v8"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Total Aset</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($totalKeseluruhan, 0, ',', '.') }}</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-green-100 text-green-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M20 6L9 17l-5-5"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Kondisi Normal</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($totalNormal, 0, ',', '.') }}</p>
            </x-card>
            <a href="{{ route('aset.perluPerhatian') }}" class="block">
                <x-card padding="p-5" class="hover:border-orange-300 transition">
                    <div class="w-[38px] h-[38px] rounded-[10px] bg-orange-100 text-orange-600 flex items-center justify-center mb-3">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                    </div>
                    <p class="text-xs font-semibold text-gray-500 mb-0.5">Perlu Perhatian <span class="font-normal text-gray-400">(Rusak/Tidak Layak)</span></p>
                    <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($totalPerluPerhatian, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-cakrawala font-semibold mt-1">Lihat daftar lengkap (+PH lewat umur, +belum dicek) &rarr;</p>
                </x-card>
            </a>
        </div>

        <x-card padding="p-3.5">
            <form method="GET" action="{{ route('aset.index') }}" class="flex flex-wrap gap-3 items-center">
                <div class="relative flex-1 min-w-[220px]">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4-4"></path></svg>
                    <x-text-input type="text" name="q" value="{{ request('q') }}" placeholder="Cari ASET ID, merek, SN, atau nama user..." class="block w-full !pl-9" />
                </div>
                @if ($ukerFilterList->isNotEmpty())
                    <x-uker-filter-combobox
                        name="uker_kode"
                        :daftar-uker="$ukerFilterList->map(fn ($u) => ['kode' => $u->kode, 'nama' => $u->nama])->toJson()"
                        :selected="request('uker_kode')"
                        :initial-label="$ukerFilterList->firstWhere('kode', request('uker_kode'))?->nama"
                        class="min-w-[190px]"
                    />
                @endif
                <x-select name="kondisi" class="min-w-[170px]">
                    <option value="">Semua Kondisi</option>
                    @foreach (\App\Models\Aset::DAFTAR_KONDISI as $k)
                        <option value="{{ $k }}" @selected(request('kondisi') == $k)>{{ $k }}</option>
                    @endforeach
                    <option value="BELUM_DIISI" @selected(request('kondisi') == 'BELUM_DIISI')>Belum Diisi</option>
                </x-select>
                <div class="flex gap-2">
                    <x-button type="submit" size="sm">Terapkan</x-button>
                    @if (request('q') || request('uker_kode') || request('kondisi') || request('kategori') || request('perlu_dicek_ulang'))
                        <x-button variant="secondary" size="sm" :href="route('aset.index')">Reset</x-button>
                    @endif
                </div>

                <div class="ml-auto flex items-center gap-2.5 pl-3 border-l border-gray-200">
                    <span class="text-[11.5px] font-semibold text-gray-500">Kepadatan</span>
                    <div class="flex bg-gray-100 rounded-lg p-1 gap-0.5">
                        <button type="button" @click="density = 'comfortable'" :class="density === 'comfortable' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700'" class="px-2.5 py-1 rounded-md text-[11.5px] font-bold transition">Lega</button>
                        <button type="button" @click="density = 'compact'" :class="density === 'compact' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700'" class="px-2.5 py-1 rounded-md text-[11.5px] font-bold transition">Padat</button>
                    </div>
                </div>
            </form>
        </x-card>

        <x-table-scroll-hint />
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100" :class="density === 'compact' ? '[&_td]:py-1.5' : '[&_td]:py-3'">
                <thead class="bg-cakrawala">
                    <tr>
                        <x-sortable-th field="no_asset">ASET ID</x-sortable-th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Uker</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Kode Aset</th>
                        <x-sortable-th field="merek">Merek / Type</x-sortable-th>
                        <x-sortable-th field="sn">SN</x-sortable-th>
                        <x-sortable-th field="tahun_perolehan">Umur</x-sortable-th>
                        <x-sortable-th field="kondisi">Kondisi</x-sortable-th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Nama User</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($asetList as $aset)
                        @php
                            $warnaKondisi = match ($aset->kondisi) { 'NORMAL' => 'green', 'RUSAK', 'TIDAK LAYAK' => 'red', default => 'gray' };
                        @endphp
                        <tr class="hover:bg-cakrawala/5 {{ \App\Support\StatusColor::aksenBorder($warnaKondisi) }}">
                            <td class="px-4 font-mono text-xs text-gray-700"><a href="{{ route('aset.show', $aset) }}" class="hover:text-cakrawala hover:underline">{{ $aset->no_asset }}</a></td>
                            <td class="px-4 text-sm text-gray-700">{{ $aset->uker?->nama }}</td>
                            <td class="px-4 text-sm text-gray-700">{{ $aset->kode_aset_kode }} - {{ $aset->kodeAset?->nama }}</td>
                            <td class="px-4 text-sm text-gray-700">{{ $aset->merek }} {{ $aset->tipe_model }}</td>
                            <td class="px-4 text-sm text-gray-700">{{ $aset->sn }}</td>
                            <td class="px-4 text-sm text-gray-700">
                                @if ($aset->tahun_perolehan)
                                    {{ $aset->umur_tahun }} thn
                                    @if ($aset->sudah_ph)
                                        <x-badge color="red" class="ml-1">PH</x-badge>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4">
                                <x-badge :color="$warnaKondisi">
                                    {{ $aset->kondisi ?? 'Belum Diisi' }}
                                </x-badge>
                            </td>
                            <td class="px-4 text-sm text-gray-700">{{ $aset->pemegang_nama }}</td>
                            <td class="px-4 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <x-icon-button variant="neutral" label="Lihat Detail" :href="route('aset.show', $aset)">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </x-icon-button>
                                    <x-icon-button type="button" variant="neutral" label="Lihat Riwayat Kondisi" @click="$store.riwayatKondisi.buka({{ $aset->id }})">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 8v4l3 3M12 22a10 10 0 100-20 10 10 0 000 20z"></path></svg>
                                    </x-icon-button>
                                    <x-icon-button variant="edit" label="Edit" :href="route('aset.edit', $aset)">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    </x-icon-button>
                                    <form action="{{ route('aset.destroy', $aset) }}" method="POST" class="inline" onsubmit="return confirm('Hapus aset ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-icon-button variant="danger" label="Hapus" type="submit">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"></path></svg>
                                        </x-icon-button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M3 8l9-5 9 5-9 5-9-5zM3 8v8l9 5 9-5V8M12 13v8"></path></svg>
                                @if (request('q') || request('uker_kode') || request('kondisi') || request('kategori') || request('perlu_dicek_ulang'))
                                    <p class="text-gray-400 text-sm mb-3">Gak ada aset yang cocok dengan filter ini.</p>
                                    <x-button variant="secondary" size="sm" :href="route('aset.index')">Reset Filter</x-button>
                                @else
                                    <p class="text-gray-400 text-sm mb-3">Belum ada data aset.</p>
                                    <x-button size="sm" :href="route('aset.create')">Tambah Aset</x-button>
                                @endif
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

    {{-- Modal Riwayat Kondisi -- fetch on-demand (Alpine store), pola sama
         kayak $store.ukerDetail di halaman Struktur Organisasi, biar gak
         perlu eager-load kondisiLogs buat semua baris di daftar yang bisa
         ribuan aset. --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('riwayatKondisi', {
                open: false,
                loading: false,
                data: null,
                async buka(asetId) {
                    this.open = true;
                    this.loading = true;
                    this.data = null;
                    try {
                        const res = await fetch(`{{ url('/aset') }}/${asetId}/kondisi-riwayat`);
                        this.data = await res.json();
                    } catch (e) {
                        this.data = { error: true };
                    }
                    this.loading = false;
                },
                tutup() {
                    this.open = false;
                },
            });
        });
    </script>

    <div
        x-show="$store.riwayatKondisi.open" x-cloak
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
        @click.self="$store.riwayatKondisi.tutup()"
        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    >
        <div
            x-show="$store.riwayatKondisi.open"
            class="bg-white rounded-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto p-6"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="font-extrabold text-lg text-gray-800">Riwayat Kondisi</h3>
                    <p class="text-xs text-gray-400 mt-0.5" x-text="$store.riwayatKondisi.data?.no_asset ?? ''"></p>
                </div>
                <button @click="$store.riwayatKondisi.tutup()" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>

            <template x-if="$store.riwayatKondisi.loading">
                <p class="text-gray-400 text-sm py-8 text-center">Memuat data...</p>
            </template>

            <template x-if="!$store.riwayatKondisi.loading && $store.riwayatKondisi.data?.error">
                <p class="text-red-500 text-sm py-8 text-center">Gagal memuat data.</p>
            </template>

            <template x-if="!$store.riwayatKondisi.loading && $store.riwayatKondisi.data && !$store.riwayatKondisi.data.error && $store.riwayatKondisi.data.logs.length === 0">
                <p class="text-gray-400 text-sm py-8 text-center">Belum ada riwayat perubahan kondisi.</p>
            </template>

            <template x-if="!$store.riwayatKondisi.loading && $store.riwayatKondisi.data && !$store.riwayatKondisi.data.error && $store.riwayatKondisi.data.logs.length > 0">
                <div class="space-y-2.5">
                    <template x-for="(log, i) in $store.riwayatKondisi.data.logs" :key="i">
                        <div class="flex items-start justify-between gap-3 text-sm border-b border-gray-100 pb-2.5">
                            <div>
                                <p class="font-semibold text-gray-700">
                                    <template x-if="log.kondisi_lama"><span x-text="log.kondisi_lama"></span></template>
                                    <template x-if="!log.kondisi_lama"><span class="text-gray-400 italic">(baru dicatat)</span></template>
                                    <span class="mx-1 text-gray-400">&rarr;</span>
                                    <span x-text="log.kondisi_baru"></span>
                                </p>
                                <p class="text-gray-400 text-xs mt-0.5" x-text="`${log.changed_by ?? '-'} · ${log.created_at}`"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</x-app-layout>
