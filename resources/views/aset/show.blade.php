<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Detail Aset</h2>
        <p class="text-xs text-gray-500 mt-0.5 font-mono">{{ $aset->no_asset }}</p>
    </x-slot>

    <div class="p-7">
        <div class="max-w-3xl mx-auto space-y-5" x-data="{ laporOpen: false }">

            <x-flash-status />

            <div class="flex items-center justify-between flex-wrap gap-2 print:hidden">
                <x-badge :color="match($aset->kondisi) { 'NORMAL' => 'green', 'RUSAK', 'TIDAK LAYAK' => 'red', default => 'gray' }">
                    {{ $aset->kondisi ?? 'Belum Diisi' }}
                </x-badge>
                <div class="flex gap-2">
                    @if ($aset->bisaDiedit(auth()->user()))
                        <x-button :href="route('aset.edit', $aset)" size="sm">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                            Edit
                        </x-button>
                    @endif
                    <x-button variant="secondary" :href="route('aset.index')" size="sm">Kembali</x-button>
                </div>
            </div>

            <x-card padding="p-6" class="print:hidden">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        <h3 class="font-extrabold text-sm text-gray-800 mb-1">QR Code Aset</h3>
                        <p class="text-xs text-gray-400">Scan buat langsung buka halaman ini -- cocok ditempel fisik di perangkat.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <img src="{{ route('aset.qrCode', $aset) }}" alt="QR Code {{ $aset->no_asset }}" class="w-24 h-24 border border-gray-100 rounded-lg">
                        <x-button type="button" variant="secondary" size="sm" onclick="window.print()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"></path></svg>
                            Print QR
                        </x-button>
                    </div>
                </div>
            </x-card>

            <div class="hidden print:block text-center py-8">
                <img src="{{ route('aset.qrCode', $aset) }}" alt="QR Code {{ $aset->no_asset }}" class="w-56 h-56 mx-auto">
                <p class="font-mono text-sm mt-2">{{ $aset->no_asset }}</p>
            </div>

            <x-card padding="p-6" class="print:hidden !border-red-100 bg-red-50/30">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        <h3 class="font-extrabold text-sm text-gray-800 mb-1">Lapor Kerusakan</h3>
                        <p class="text-xs text-gray-500">Nemuin masalah fisik di perangkat ini? Laporin langsung dari sini, boleh sertakan foto.</p>
                    </div>
                    <x-button type="button" variant="secondary" size="sm" @click="laporOpen = true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                        Lapor Kerusakan
                    </x-button>
                </div>

                <div
                    x-show="laporOpen" x-cloak
                    class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4"
                    @click.self="laporOpen = false"
                    x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                >
                    <div
                        x-show="laporOpen"
                        class="bg-white p-6 rounded-2xl max-w-md w-full text-left"
                        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                    >
                        <h3 class="font-extrabold text-sm text-gray-800 mb-1">Lapor Kerusakan</h3>
                        <p class="text-xs text-gray-400 mb-3.5">{{ $aset->no_asset }} &middot; {{ $aset->uker?->nama }}</p>
                        <form action="{{ route('monitoring.laporanAset.store', $aset) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <div>
                                <x-input-label value="Deskripsi Kerusakan" />
                                <textarea name="deskripsi" rows="3" required class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" placeholder="contoh: layar berkedip-kedip, mati total, dst"></textarea>
                            </div>
                            <div>
                                <x-input-label value="Foto (opsional)" />
                                <input type="file" name="foto" accept="image/*" capture="environment" @change="await window.kompresFotoInput($event.target)" class="mt-1.5 block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                            </div>
                            <div class="flex gap-2 pt-1">
                                <x-button type="submit">Kirim Laporan</x-button>
                                <x-button type="button" variant="secondary" @click="laporOpen = false">Batal</x-button>
                            </div>
                        </form>
                    </div>
                </div>
            </x-card>

            <x-card padding="p-6" class="print:hidden">
                <h3 class="font-extrabold text-sm text-gray-800 mb-4">Identitas Aset</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-0.5">Aset ID</p>
                        <p class="text-sm font-mono text-gray-800">{{ $aset->no_asset }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-0.5">Uker</p>
                        <p class="text-sm text-gray-800">{{ $aset->uker?->nama }} <span class="text-gray-400 font-mono text-xs">({{ $aset->uker_kode }})</span></p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-0.5">Kode Aset / Kategori</p>
                        <p class="text-sm text-gray-800">{{ $aset->kode_aset_kode }} - {{ $aset->kodeAset?->nama }}</p>
                        <p class="text-xs text-gray-400">{{ $aset->kodeAset?->kategori }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-0.5">Merek / Type</p>
                        <p class="text-sm text-gray-800">{{ $aset->merek }} {{ $aset->tipe_model }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-0.5">Serial Number (SN)</p>
                        <p class="text-sm text-gray-800 font-mono">{{ $aset->sn }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-0.5">Kapasitas Memori</p>
                        <p class="text-sm text-gray-800">{{ $aset->kapasitas_memori ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-0.5">Tahun Distribusi / Umur</p>
                        <p class="text-sm text-gray-800">
                            @if ($aset->tahun_perolehan)
                                {{ $aset->tahun_perolehan }} &middot; {{ $aset->umur_tahun }} thn
                                @if ($aset->sudah_ph)
                                    <x-badge color="red" class="ml-1">PH</x-badge>
                                @endif
                            @else
                                -
                            @endif
                        </p>
                    </div>
                </div>
            </x-card>

            <x-card padding="p-6" class="print:hidden">
                <h3 class="font-extrabold text-sm text-gray-800 mb-1">Data Pemegang & Keamanan</h3>
                <p class="text-xs text-gray-400 mb-4">
                    @if (in_array($aset->kodeAset?->kategori, \App\Models\Aset::KATEGORI_PEMEGANG_INDIVIDU))
                        Kategori aset ini dipegang 1 orang pengguna.
                    @else
                        Kategori aset ini gak punya pemegang individu.
                    @endif
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-0.5">Nama User (Pemegang)</p>
                        <p class="text-sm text-gray-800">{{ $aset->pemegang_nama ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-0.5">Jabatan</p>
                        <p class="text-sm text-gray-800">{{ $aset->jabatan ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-0.5">Personal Number (PN)</p>
                        <p class="text-sm text-gray-800 font-mono">{{ $aset->pemegang_pn ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-0.5">IP Address</p>
                        <p class="text-sm text-gray-800 font-mono">{{ $aset->ip_address ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-1.5">Status Hardening</p>
                        @if ($aset->status_hardening)
                            <x-badge :color="$aset->status_hardening === 'Sudah' ? 'green' : 'red'">{{ $aset->status_hardening }}</x-badge>
                        @else
                            <span class="text-sm text-gray-400">-</span>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-1.5">Status Bitlocker</p>
                        @if ($aset->status_bitlocker)
                            <x-badge :color="$aset->status_bitlocker === 'Aktif' ? 'green' : 'red'">{{ $aset->status_bitlocker }}</x-badge>
                        @else
                            <span class="text-sm text-gray-400">-</span>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-1.5">Status DLP</p>
                        @if ($aset->status_dlp)
                            <x-badge :color="$aset->status_dlp === 'Aktif' ? 'green' : 'red'">{{ $aset->status_dlp }}</x-badge>
                        @else
                            <span class="text-sm text-gray-400">-</span>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-1.5">Status Antivirus</p>
                        @if ($aset->status_antivirus)
                            <x-badge :color="$aset->status_antivirus === 'Aktif' ? 'green' : 'red'">{{ $aset->status_antivirus }}</x-badge>
                        @else
                            <span class="text-sm text-gray-400">-</span>
                        @endif
                    </div>
                </div>
            </x-card>

            <x-card padding="p-6" class="print:hidden">
                <h3 class="font-extrabold text-sm text-gray-800 mb-2">Keterangan</h3>
                <p class="text-sm text-gray-600 whitespace-pre-line">{{ $aset->keterangan ?: '-' }}</p>
            </x-card>

            <x-card padding="p-6" class="print:hidden">
                <h3 class="font-extrabold text-sm text-gray-800 mb-1">Riwayat Aset</h3>
                <p class="text-xs text-gray-400 mb-4">Gabungan kronologis perubahan kondisi, mutasi uker, permintaan edit, dan laporan kerusakan.</p>
                @if ($timeline->isEmpty())
                    <p class="text-gray-400 text-sm">Belum ada riwayat buat aset ini.</p>
                @else
                    <div class="relative pl-6">
                        <div class="absolute left-[7px] top-1.5 bottom-1.5 w-px bg-gray-200"></div>
                        <div class="space-y-5">
                            @foreach ($timeline as $t)
                                <div class="relative">
                                    <span class="absolute -left-6 top-1 w-3.5 h-3.5 rounded-full ring-2 ring-white {{ match($t['jenis']) { 'kondisi' => 'bg-cakrawala', 'mutasi' => 'bg-purple-500', 'edit' => 'bg-yellow-500', 'kendala' => 'bg-red-500', default => 'bg-gray-400' } }}"></span>
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">{{ $t['judul'] }}</p>
                                            <p class="text-sm text-gray-700 mt-0.5">{{ $t['deskripsi'] }}</p>
                                            @if ($t['catatan'])
                                                <p class="text-gray-400 text-xs mt-0.5">Catatan: {{ $t['catatan'] }}</p>
                                            @endif
                                            @if ($t['foto_url'])
                                                <a href="{{ $t['foto_url'] }}" target="_blank" rel="noopener">
                                                    <img src="{{ $t['foto_url'] }}" alt="Foto" class="w-14 h-14 rounded-lg object-cover border border-gray-100 mt-1.5">
                                                </a>
                                            @endif
                                            <p class="text-gray-400 text-xs mt-1">{{ $t['oleh'] ?? '-' }} &middot; {{ $t['created_at']?->format('d M Y H:i') }}</p>
                                        </div>
                                        @if ($t['badge'])
                                            <x-badge :color="$t['badge']['color']" class="flex-none">{{ $t['badge']['label'] }}</x-badge>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </x-card>

        </div>
    </div>
</x-app-layout>
