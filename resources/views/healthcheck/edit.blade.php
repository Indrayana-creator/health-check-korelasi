<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            Isi Health Check &mdash; {{ $healthcheck->uker?->nama }} ({{ $healthcheck->periode }})
        </h2>
    </x-slot>

    <div class="p-7">
        <div class="max-w-4xl mx-auto space-y-5">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">{{ session('status') }}</div>
            @endif

            {{-- Status approval + tombol aksi workflow --}}
            <div @if ($healthcheck->status_approval === 'Menunggu Approval' && auth()->user()->role === 'admin') x-data="{ openReject: false }" @endif>
            <x-card padding="p-6">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <h3 class="font-extrabold text-sm text-gray-800 mb-1.5">Status Approval</h3>
                        <x-badge :color="match($healthcheck->status_approval) { 'Disetujui' => 'green', 'Menunggu Approval' => 'yellow', 'Ditolak' => 'red', default => 'gray' }">
                            {{ $healthcheck->status_approval }}
                        </x-badge>
                        @if ($healthcheck->status_approval === 'Ditolak' && $healthcheck->catatan_approval)
                            <p class="text-sm text-red-600 mt-2">Alasan ditolak: {{ $healthcheck->catatan_approval }}</p>
                        @endif
                        @if ($healthcheck->status_approval === 'Disetujui')
                            <p class="text-xs text-gray-400 mt-2">Disetujui oleh PN {{ $healthcheck->approved_by_pn }} pada {{ $healthcheck->approved_at?->format('d M Y H:i') }}</p>
                        @endif
                    </div>

                    <div class="flex gap-2">
                        @if ($healthcheck->itemsBisaDiedit())
                            <form action="{{ route('healthcheck.submit', $healthcheck) }}" method="POST" onsubmit="return confirm('Submit form ini untuk approval? Item checklist tidak bisa diedit lagi sampai disetujui/ditolak.')">
                                @csrf
                                <x-button type="submit">Submit untuk Approval</x-button>
                            </form>
                        @endif

                        @if ($healthcheck->status_approval === 'Menunggu Approval' && auth()->user()->role === 'admin')
                            <form action="{{ route('healthcheck.approve', $healthcheck) }}" method="POST" onsubmit="return confirm('Setujui form ini?')">
                                @csrf
                                <x-button type="submit" variant="success">Approve</x-button>
                            </form>
                            <x-button type="button" variant="danger" @click="openReject = true">Tolak</x-button>
                        @endif
                    </div>
                </div>
            </x-card>

            {{-- Modal kecil buat alasan tolak --}}
            @if ($healthcheck->status_approval === 'Menunggu Approval' && auth()->user()->role === 'admin')
                <div x-show="openReject" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="openReject = false">
                    <div class="bg-white p-6 rounded-2xl max-w-md w-full">
                        <h3 class="font-extrabold text-sm text-gray-800 mb-3">Alasan Penolakan</h3>
                        <form action="{{ route('healthcheck.reject', $healthcheck) }}" method="POST">
                            @csrf
                            <textarea name="catatan_approval" rows="3" class="w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala mb-3" placeholder="Jelaskan apa yang perlu direvisi..." required></textarea>
                            <div class="flex gap-2">
                                <x-button type="submit" variant="danger">Tolak &amp; Kirim</x-button>
                                <x-button type="button" variant="secondary" @click="openReject = false">Batal</x-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
            </div>

            <form action="{{ route('healthcheck.update', $healthcheck) }}" method="POST" class="space-y-5" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <x-card padding="p-6">
                    <h3 class="font-extrabold text-sm text-gray-800 mb-1.5">Status Tindak Lanjut</h3>
                    <p class="text-xs text-gray-400 mb-4">Diisi kalau ada item yang bermasalah (Not OK) dan perlu ditindaklanjuti/diperbaiki di lapangan. Field ini tetap bisa diisi meskipun form sudah disetujui.</p>

                    <div class="mb-4">
                        <x-input-label value="Status" />
                        <x-select name="status_tindak_lanjut" class="mt-1.5 block w-full">
                            @foreach (\App\Models\HealthCheckForm::DAFTAR_STATUS_TINDAK_LANJUT as $s)
                                <option value="{{ $s }}" @selected($healthcheck->status_tindak_lanjut === $s)>{{ $s }}</option>
                            @endforeach
                        </x-select>
                    </div>

                    <div>
                        <x-input-label value="Catatan Tindak Lanjut (opsional)" />
                        <textarea name="catatan_tindak_lanjut" rows="3" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" placeholder="contoh: sudah diajukan perbaikan AC ruang server ke vendor, estimasi selesai 3 hari">{{ $healthcheck->catatan_tindak_lanjut }}</textarea>
                    </div>
                </x-card>

                @if (!$healthcheck->itemsBisaDiedit())
                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm p-4 rounded-xl">
                        @if ($healthcheck->sudahLewatTanggal())
                            Item checklist & dokumentasi visual di bawah ini terkunci (read-only) karena sudah melewati tanggal pemeriksaan ({{ $healthcheck->tanggal_pemeriksaan->format('d M Y') }}). Pengisian harus dilakukan di hari yang sama, tidak bisa mengisi mundur.
                        @else
                            Item checklist & dokumentasi visual di bawah ini terkunci (read-only) karena form sudah {{ strtolower($healthcheck->status_approval) }}.
                        @endif
                        Cuma Status Tindak Lanjut di atas yang masih bisa diubah.
                    </div>
                @endif

                @php
                    // Progress per kategori (berapa item yang statusnya udah bukan
                    // "Belum Diperiksa") -- dipakai buat progress bar di tab & nentuin
                    // tab mana yang otomatis kebuka duluan pas form dibuka/dibuka lagi.
                    $editable = $healthcheck->itemsBisaDiedit();
                    $kategoriProgress = [];
                    $tabAwal = 0;
                    foreach ($itemsByKategori as $kategori => $items) {
                        $total = $items->count();
                        $selesai = $items->where('status', '!=', 'Belum Diperiksa')->count();
                        $kategoriProgress[] = ['total' => $total, 'selesai' => $selesai];
                    }
                    foreach ($kategoriProgress as $i => $p) {
                        if ($p['selesai'] < $p['total']) {
                            $tabAwal = $i;
                            break;
                        }
                    }
                    $overallTotal = array_sum(array_column($kategoriProgress, 'total'));
                    $overallSelesai = array_sum(array_column($kategoriProgress, 'selesai'));
                    $overallPct = $overallTotal > 0 ? round($overallSelesai / $overallTotal * 100) : 0;

                    // Tab E "Dokumentasi Visual" -- bukan checklist OK/Not OK/N/A,
                    // gak ikut $kategoriProgress/$overallPct (compliance % tetap
                    // murni dari A-D). Progress-nya dihitung terpisah (0-3 foto).
                    $tabDokumentasiVisual = count($kategoriProgress);
                    $fotoTerisi = $healthcheck->jumlahFotoDokumentasiTerisi();
                    $fotoTotal = count(\App\Models\HealthCheckForm::FIELD_DOKUMENTASI_VISUAL);

                    $statusActiveClass = [
                        'Belum Diperiksa' => 'bg-gray-500 text-white border-gray-500',
                        'OK' => 'bg-green-600 text-white border-green-600',
                        'Not OK' => 'bg-red-600 text-white border-red-600',
                        'N/A' => 'bg-gray-400 text-white border-gray-400',
                    ];
                @endphp

                <div x-data="{ tab: {{ $tabAwal }} }">
                    {{-- Tab per kategori checklist (dinamis dari config) -- bebas diklik urutan apa aja, progress
                         per kategori kesimpen tiap kali "Simpan" ditekan (item yang
                         belum disentuh di kategori lain gak ikut berubah/ilang). --}}
                    <x-card padding="p-1.5" class="mb-4">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($itemsByKategori as $kategori => $items)
                                @php
                                    $p = $kategoriProgress[$loop->index];
                                    $pct = $p['total'] > 0 ? round($p['selesai'] / $p['total'] * 100) : 0;
                                    $lengkap = $p['selesai'] === $p['total'];
                                @endphp
                                <button
                                    type="button"
                                    @click="tab = {{ $loop->index }}"
                                    :class="tab === {{ $loop->index }} ? 'bg-cakrawala/10' : 'hover:bg-gray-50'"
                                    class="flex-1 min-w-[160px] flex flex-col gap-1.5 p-3 rounded-lg text-left transition"
                                >
                                    <span :class="tab === {{ $loop->index }} ? 'text-cakrawala' : 'text-gray-600'" class="text-xs font-bold">{{ $kategori }}</span>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-[5px] rounded-full bg-gray-200">
                                            <div class="h-[5px] rounded-full {{ $lengkap ? 'bg-green-500' : 'bg-cakrawala' }}" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <span :class="tab === {{ $loop->index }} ? 'text-cakrawala' : 'text-gray-500'" class="text-[10.5px] font-bold whitespace-nowrap">{{ $p['selesai'] }}/{{ $p['total'] }}</span>
                                    </div>
                                </button>
                            @endforeach

                            <button
                                type="button"
                                @click="tab = {{ $tabDokumentasiVisual }}"
                                :class="tab === {{ $tabDokumentasiVisual }} ? 'bg-cakrawala/10' : 'hover:bg-gray-50'"
                                class="flex-1 min-w-[160px] flex flex-col gap-1.5 p-3 rounded-lg text-left transition"
                            >
                                <span :class="tab === {{ $tabDokumentasiVisual }} ? 'text-cakrawala' : 'text-gray-600'" class="text-xs font-bold">E - Dokumentasi Visual</span>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-[5px] rounded-full bg-gray-200">
                                        <div class="h-[5px] rounded-full {{ $fotoTerisi === $fotoTotal ? 'bg-green-500' : 'bg-cakrawala' }}" style="width: {{ $fotoTotal > 0 ? round($fotoTerisi / $fotoTotal * 100) : 0 }}%"></div>
                                    </div>
                                    <span :class="tab === {{ $tabDokumentasiVisual }} ? 'text-cakrawala' : 'text-gray-500'" class="text-[10.5px] font-bold whitespace-nowrap">{{ $fotoTerisi }}/{{ $fotoTotal }}</span>
                                </div>
                            </button>
                        </div>
                    </x-card>

                    @php $globalIndex = 0; @endphp

                    @foreach ($itemsByKategori as $kategori => $items)
                        <div x-show="tab === {{ $loop->index }}" x-cloak>
                            <x-card padding="p-6">
                                <h3 class="font-extrabold text-sm text-gray-800 mb-1">{{ $kategori }}</h3>
                                <p class="text-xs text-gray-400 mb-4">{{ $items->count() }} item pemeriksaan</p>

                                <div class="flex flex-col">
                                    @foreach ($items as $item)
                                        <div class="border-b border-gray-100 py-3.5 first:pt-0" x-data="{ status: '{{ $item->status }}' }">
                                            <input type="hidden" name="items[{{ $globalIndex }}][id]" value="{{ $item->id }}">
                                            <input type="hidden" name="items[{{ $globalIndex }}][status]" :value="status">
                                            <p class="text-sm text-gray-700 mb-2.5">{{ $loop->iteration }}. {{ $item->item_pemeriksaan }}</p>

                                            <div class="flex flex-wrap items-center gap-1.5">
                                                @foreach (['Belum Diperiksa', 'OK', 'Not OK', 'N/A'] as $opsi)
                                                    <button
                                                        type="button"
                                                        @disabled(!$editable)
                                                        @if ($editable) @click="status = '{{ $opsi }}'" @endif
                                                        :class="status === '{{ $opsi }}' ? '{{ $statusActiveClass[$opsi] }}' : 'bg-white text-gray-600 border-gray-200'"
                                                        class="px-3 py-1.5 rounded-lg border text-xs font-bold transition {{ $editable ? 'hover:bg-gray-50 cursor-pointer' : 'opacity-60 cursor-not-allowed' }}"
                                                    >{{ $opsi }}</button>
                                                @endforeach

                                                <x-text-input
                                                    type="text"
                                                    name="items[{{ $globalIndex }}][catatan]"
                                                    value="{{ $item->catatan }}"
                                                    placeholder="Catatan (opsional)"
                                                    class="flex-1 min-w-[180px]"
                                                    x-show="status === 'Not OK'"
                                                    :readonly="!$editable"
                                                />
                                            </div>

                                            {{-- Foto bukti kondisi fisik -- opsional, ganti foto lama kalau
                                                 upload baru (dihandle di controller). --}}
                                            <div class="flex items-center gap-2.5 mt-2.5">
                                                @if ($item->foto_url)
                                                    <a href="{{ $item->foto_url }}" target="_blank" rel="noopener">
                                                        <img src="{{ $item->foto_url }}" alt="Foto bukti" class="w-10 h-10 rounded-lg object-cover border border-gray-100">
                                                    </a>
                                                @endif
                                                @if ($editable)
                                                    <input
                                                        type="file"
                                                        name="items[{{ $globalIndex }}][foto]"
                                                        accept="image/*"
                                                        class="text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border file:border-gray-200 file:bg-white file:text-xs file:font-semibold file:text-gray-600 hover:file:bg-gray-50"
                                                    >
                                                @endif
                                            </div>
                                        </div>
                                        @php $globalIndex++; @endphp
                                    @endforeach
                                </div>
                            </x-card>

                            <div class="flex items-center justify-between mt-3">
                                <x-button
                                    type="button"
                                    variant="secondary"
                                    x-show="tab > 0"
                                    @click="tab = tab - 1"
                                >&larr; Kategori Sebelumnya</x-button>
                                <span x-show="tab === 0"></span>
                                <x-button
                                    type="button"
                                    variant="secondary"
                                    x-show="tab < {{ $tabDokumentasiVisual }}"
                                    @click="tab = tab + 1"
                                >Kategori Berikutnya &rarr;</x-button>
                            </div>
                        </div>
                    @endforeach

                    <div x-show="tab === {{ $tabDokumentasiVisual }}" x-cloak>
                        <x-card padding="p-6">
                            <h3 class="font-extrabold text-sm text-gray-800 mb-1">E - Dokumentasi Visual</h3>
                            <p class="text-xs text-gray-400 mb-4">
                                Link/URL foto bukti pemeriksaan, opsional -- tidak ikut hitungan compliance % (compliance tetap murni dari kategori checklist item, bukan dari sini).
                            </p>

                            <div class="flex flex-col gap-5">
                                @foreach (\App\Models\HealthCheckForm::FIELD_DOKUMENTASI_VISUAL as $field => $meta)
                                    <div class="border-b border-gray-100 pb-5 last:border-0 last:pb-0">
                                        <p class="text-sm font-semibold text-gray-700 mb-1">{{ $loop->iteration }}. {{ $meta['label'] }}</p>
                                        <p class="text-xs text-gray-400 mb-1">{{ $meta['instruksi'] }}</p>
                                        <p class="text-xs text-gray-400 mb-2.5">Kondisi ideal: {{ $meta['kondisi_ideal'] }}</p>
                                        <x-text-input
                                            type="text"
                                            name="{{ $field }}"
                                            value="{{ $healthcheck->{$field} }}"
                                            placeholder="https://... (link foto/screenshot)"
                                            class="block w-full"
                                            :readonly="!$editable"
                                        />
                                    </div>
                                @endforeach
                            </div>
                        </x-card>

                        <div class="flex items-center justify-between mt-3">
                            <x-button
                                type="button"
                                variant="secondary"
                                @click="tab = tab - 1"
                            >&larr; Kategori Sebelumnya</x-button>
                            <span></span>
                        </div>
                    </div>
                </div>

                {{-- Simpan selalu keliatan di step manapun -- submit nyimpen SEMUA
                     kategori (bukan cuma yang lagi dibuka), jadi aman ditekan kapan
                     aja mau berhenti isi, gak harus nunggu sampai kategori terakhir. --}}
                <div class="sticky bottom-0 bg-gray-50/95 backdrop-blur-sm -mx-7 px-7 py-4 border-t border-gray-200 flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-3 flex-1 min-w-[220px] max-w-md">
                        <div class="flex-1 h-2 rounded-full bg-gray-200">
                            <div class="h-2 rounded-full bg-gradient-to-r from-nusantara to-cakrawala" style="width: {{ $overallPct }}%"></div>
                        </div>
                        <span class="text-xs font-bold text-gray-600 whitespace-nowrap">{{ $overallSelesai }}/{{ $overallTotal }} diperiksa</span>
                    </div>
                    <div class="flex gap-2">
                        <x-button type="submit" class="px-6 py-2.5">Simpan</x-button>
                        <x-button variant="secondary" :href="route('healthcheck.index')" class="px-5 py-2.5">Kembali</x-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
