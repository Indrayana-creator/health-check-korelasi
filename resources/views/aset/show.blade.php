<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Detail Aset</h2>
        <p class="text-xs text-gray-500 mt-0.5 font-mono">{{ $aset->no_asset }}</p>
    </x-slot>

    <div class="p-7">
        <div class="max-w-3xl mx-auto space-y-5">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">{{ session('status') }}</div>
            @endif

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
                <h3 class="font-extrabold text-sm text-gray-800 mb-4">Riwayat Perubahan Kondisi</h3>
                @if ($aset->kondisiLogs->isEmpty())
                    <p class="text-gray-400 text-sm">Belum ada riwayat perubahan kondisi.</p>
                @else
                    <div class="space-y-2.5">
                        @foreach ($aset->kondisiLogs as $log)
                            <div class="flex items-start justify-between gap-3 text-sm border-b border-gray-100 pb-2.5">
                                <div>
                                    <p class="font-semibold text-gray-700">
                                        @if ($log->kondisi_lama)
                                            {{ $log->kondisi_lama }}
                                        @else
                                            <span class="text-gray-400 italic">(baru dicatat)</span>
                                        @endif
                                        <span class="mx-1 text-gray-400">&rarr;</span>
                                        {{ $log->kondisi_baru }}
                                    </p>
                                    <p class="text-gray-400 text-xs mt-0.5">{{ $log->changedBy?->name ?? '-' }} &middot; {{ $log->created_at?->format('d M Y H:i') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

            <x-card padding="p-6" class="print:hidden">
                <h3 class="font-extrabold text-sm text-gray-800 mb-4">Riwayat Permintaan Edit</h3>
                @if ($aset->editRequests->isEmpty())
                    <p class="text-gray-400 text-sm">Belum ada permintaan edit buat aset ini.</p>
                @else
                    <div class="space-y-2.5">
                        @foreach ($aset->editRequests as $r)
                            <div class="flex items-start justify-between gap-3 text-sm border-b border-gray-100 pb-2.5">
                                <div>
                                    <p class="text-gray-700">{{ $r->alasan ?: '(tanpa alasan)' }}</p>
                                    <p class="text-gray-400 text-xs mt-0.5">
                                        Diajukan {{ $r->requester?->name ?? '-' }} &middot; {{ $r->created_at?->format('d M Y H:i') }}
                                        @if ($r->catatan_admin)
                                            &middot; Catatan: {{ $r->catatan_admin }}
                                        @endif
                                    </p>
                                </div>
                                <x-badge :color="match($r->status) { 'Disetujui' => 'green', 'Menunggu' => 'yellow', 'Ditolak' => 'red', default => 'gray' }" class="flex-none">
                                    {{ $r->status }}
                                </x-badge>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

        </div>
    </div>
</x-app-layout>
