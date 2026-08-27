<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            Isi Health Check &mdash; {{ $healthcheck->uker?->nama }} ({{ $healthcheck->periode }})
        </h2>
    </x-slot>

    <div class="p-7">
        <div class="max-w-4xl mx-auto space-y-5">

            <x-flash-status />

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
                <div
                    x-show="openReject" x-cloak
                    class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4"
                    @click.self="openReject = false"
                    x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                >
                    <div
                        x-show="openReject"
                        class="bg-white p-6 rounded-2xl max-w-md w-full"
                        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                    >
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

            <form
                action="{{ route('healthcheck.update', $healthcheck) }}" method="POST" class="space-y-5" enctype="multipart/form-data"
                x-data="{ menyimpan: false }"
                @submit="menyimpan = true"
            >
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

                    // Jalan pintas dari scan QR Ruangan -- kalau ?kategori=
                    // cocok sama salah satu kategori form ini, buka LANGSUNG
                    // ke tab itu, ketimbang tab "belum lengkap" default di atas.
                    if (request()->filled('kategori')) {
                        $indexKategoriTujuan = $itemsByKategori->keys()->search(request('kategori'));
                        if ($indexKategoriTujuan !== false) {
                            $tabAwal = $indexKategoriTujuan;
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
                                            <div
                                                x-data="{ w: 0 }" x-init="setTimeout(() => w = {{ $pct }}, 50)"
                                                class="h-[5px] rounded-full transition-all duration-700 ease-out {{ $lengkap ? 'bg-green-500' : 'bg-cakrawala' }}"
                                                :style="'width: ' + w + '%'"
                                            ></div>
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
                                        @php $pctDokumentasi = $fotoTotal > 0 ? round($fotoTerisi / $fotoTotal * 100) : 0; @endphp
                                        <div
                                            x-data="{ w: 0 }" x-init="setTimeout(() => w = {{ $pctDokumentasi }}, 50)"
                                            class="h-[5px] rounded-full transition-all duration-700 ease-out {{ $fotoTerisi === $fotoTotal ? 'bg-green-500' : 'bg-cakrawala' }}"
                                            :style="'width: ' + w + '%'"
                                        ></div>
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
                                Upload foto bukti pemeriksaan (kamera langsung atau galeri), opsional -- tidak ikut hitungan compliance % (compliance tetap murni dari kategori checklist item, bukan dari sini).
                            </p>

                            <div class="flex flex-col gap-5">
                                @foreach (\App\Models\HealthCheckForm::FIELD_DOKUMENTASI_VISUAL as $field => $meta)
                                    @php $fotoTersimpan = $healthcheck->fotoUntukField($field); @endphp
                                    <div
                                        class="border-b border-gray-100 pb-5 last:border-0 last:pb-0"
                                        x-data="{
                                            fotos: [],
                                            sisaKuota: {{ max(0, \App\Models\HealthCheckForm::MAKS_FOTO_DOKUMENTASI_PER_KATEGORI - $fotoTersimpan->count()) }},
                                            async tambah(fileList) {
                                                const sisa = this.sisaKuota - this.fotos.length;
                                                if (sisa <= 0) {
                                                    alert('Maksimal {{ \App\Models\HealthCheckForm::MAKS_FOTO_DOKUMENTASI_PER_KATEGORI }} foto per kategori. Hapus salah satu dulu kalau mau tambah lagi.');
                                                    return;
                                                }
                                                for (const f of Array.from(fileList).slice(0, sisa)) {
                                                    const kompres = await window.kompresFoto(f);
                                                    this.fotos.push({ file: kompres, url: URL.createObjectURL(kompres) });
                                                }
                                                this.sinkron();
                                            },
                                            hapusPreview(i) {
                                                URL.revokeObjectURL(this.fotos[i].url);
                                                this.fotos.splice(i, 1);
                                                this.sinkron();
                                            },
                                            // Input file asli TIDAK diklik user langsung (2 tombol Ambil Foto/
                                            // Upload dari Galeri itu input TERPISAH & gak ikut disubmit) --
                                            // isinya disinkronkan dari array fotos lewat DataTransfer tiap kali
                                            // foto ditambah/dihapus, biar bisa batalin 1 foto sebelum Simpan.
                                            sinkron() {
                                                const dt = new DataTransfer();
                                                this.fotos.forEach((f) => dt.items.add(f.file));
                                                this.$refs.inputAsli.files = dt.files;
                                            },
                                        }"
                                    >
                                        <p class="text-sm font-semibold text-gray-700 mb-1">{{ $loop->iteration }}. {{ $meta['label'] }}</p>
                                        <p class="text-xs text-gray-400 mb-1">{{ $meta['instruksi'] }}</p>
                                        <p class="text-xs text-gray-400 mb-2.5">Kondisi ideal: {{ $meta['kondisi_ideal'] }}</p>

                                        {{-- Galeri foto yang UDAH TERSIMPAN -- boleh lebih dari 1, tiap foto
                                             punya checkbox hapus sendiri (dicentang = foto itu ke-hapus pas
                                             "Simpan" ditekan, bukan langsung hilang saat itu juga). --}}
                                        @if ($fotoTersimpan->isNotEmpty())
                                            <div class="flex flex-wrap gap-3 mb-3">
                                                @foreach ($fotoTersimpan as $foto)
                                                    <div class="relative" x-data="{ hapus: false }">
                                                        <a href="{{ $foto->url }}" target="_blank" rel="noopener">
                                                            <img src="{{ $foto->url }}" alt="{{ $meta['label'] }}" class="w-24 h-24 object-cover rounded-lg border border-gray-100" :class="hapus && 'opacity-30'">
                                                        </a>
                                                        @if ($editable)
                                                            <label class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-white border border-gray-200 flex items-center justify-center cursor-pointer shadow-sm hover:bg-red-50" title="Hapus foto ini">
                                                                <input type="checkbox" name="hapus_foto_dokumentasi[]" value="{{ $foto->id }}" x-model="hapus" class="hidden">
                                                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" class="w-3.5 h-3.5" :class="hapus ? 'text-red-600' : 'text-gray-500'" stroke="currentColor"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                                                            </label>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        {{-- Preview foto BARU yang baru dipilih/difoto -- client-side, belum
                                             ke-upload/tersimpan, biar user bisa lihat dulu sebelum "Simpan".
                                             Bisa dibatalin satu-satu (tombol x) sebelum sempat disimpan. --}}
                                        <template x-if="fotos.length">
                                            <div class="flex flex-wrap gap-3 mb-2">
                                                <template x-for="(f, i) in fotos" :key="i">
                                                    <div class="relative">
                                                        <img :src="f.url" alt="Preview foto baru" class="w-24 h-24 object-cover rounded-lg border-2 border-cakrawala">
                                                        <button
                                                            type="button"
                                                            @click="hapusPreview(i)"
                                                            class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-white border border-gray-200 flex items-center justify-center shadow-sm hover:bg-red-50"
                                                            title="Batalkan foto ini"
                                                        >
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="w-3.5 h-3.5 text-gray-500"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        <p class="text-[11px] text-cakrawala font-semibold mb-2" x-show="fotos.length" x-cloak x-text="fotos.length + ' foto baru dipilih (belum disimpan)'"></p>

                                        @if ($editable)
                                            {{-- 2 tombol terpisah -- "Ambil Foto" (paksa buka kamera langsung
                                                 via capture) dan "Upload dari Galeri" (buka file/galeri biasa,
                                                 bisa pilih banyak sekaligus). Input di sini TERPISAH dari yang
                                                 beneran disubmit (x-ref="inputAsli") -- tiap file yang dipilih
                                                 dikompres dulu (window.kompresFoto) baru masuk ke situ, biar
                                                 upload ringan walau originalnya gede. --}}
                                            <div class="flex flex-wrap gap-2">
                                                <label
                                                    for="dokvisual-{{ $field }}-kamera"
                                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg font-semibold whitespace-nowrap transition bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 px-4 py-2 text-sm cursor-pointer"
                                                >
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                                                    Ambil Foto
                                                </label>
                                                <input
                                                    id="dokvisual-{{ $field }}-kamera"
                                                    type="file"
                                                    accept="image/*"
                                                    capture="environment"
                                                    @change="await tambah($event.target.files); $event.target.value = ''"
                                                    class="hidden"
                                                >

                                                <label
                                                    for="dokvisual-{{ $field }}-galeri"
                                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg font-semibold whitespace-nowrap transition bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 px-4 py-2 text-sm cursor-pointer"
                                                >
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 4h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z"></path><circle cx="9" cy="9" r="1.5"></circle></svg>
                                                    Upload dari Galeri
                                                </label>
                                                <input
                                                    id="dokvisual-{{ $field }}-galeri"
                                                    type="file"
                                                    multiple
                                                    accept="image/*"
                                                    @change="await tambah($event.target.files); $event.target.value = ''"
                                                    class="hidden"
                                                >
                                            </div>
                                            {{-- Input yang beneran disubmit -- isinya disinkronkan dari JS
                                                 (sinkron()), bukan diisi langsung sama user. --}}
                                            <input type="file" name="{{ $field }}[]" x-ref="inputAsli" multiple class="hidden">
                                            <p class="text-[11px] text-gray-400 mt-1.5">Maks {{ \App\Models\HealthCheckForm::MAKS_FOTO_DOKUMENTASI_PER_KATEGORI }} foto per kategori, otomatis dikompres. Foto yang sudah ada tetap aman kecuali dicentang hapus di atas.</p>
                                        @endif
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
                            <div
                                x-data="{ w: 0 }" x-init="setTimeout(() => w = {{ $overallPct }}, 50)"
                                class="h-2 rounded-full bg-gradient-to-r from-nusantara to-cakrawala transition-all duration-700 ease-out"
                                :style="'width: ' + w + '%'"
                            ></div>
                        </div>
                        <span class="text-xs font-bold text-gray-600 whitespace-nowrap">{{ $overallSelesai }}/{{ $overallTotal }} diperiksa</span>
                    </div>
                    <div class="flex gap-2">
                        <x-button type="submit" class="px-6 py-2.5" x-bind:disabled="menyimpan">
                            <span x-show="!menyimpan">Simpan</span>
                            <span x-show="menyimpan" x-cloak class="inline-flex items-center gap-1.5">
                                <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4 animate-spin"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-opacity="0.25"></circle><path d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path></svg>
                                Menyimpan...
                            </span>
                        </x-button>
                        <x-button variant="secondary" :href="route('healthcheck.index')" class="px-5 py-2.5">Kembali</x-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
