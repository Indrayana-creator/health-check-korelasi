<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Aset
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    action="{{ route('aset.update', $aset) }}"
                    method="POST"
                    class="space-y-4"
                    x-data="{
                        semuaUker: {{ $ukerList->map(fn($u) => ['kode' => $u->kode, 'nama' => $u->nama, 'kode_spv' => $u->kode_spv, 'uker_spv' => $u->uker_spv])->toJson() }},
                        kodeIndukTerpilih: '{{ old('kode_induk_terpilih', $aset->uker?->kode_spv) }}',
                        ukerKodeTerpilih: '{{ old('uker_kode', $aset->uker_kode) }}',
                        get daftarInduk() {
                            const map = {};
                            this.semuaUker.forEach(u => { map[u.kode_spv] = u.uker_spv; });
                            return Object.entries(map).map(([kode_spv, uker_spv]) => ({ kode_spv, uker_spv }));
                        },
                        get anggotaUker() {
                            if (!this.kodeIndukTerpilih) return [];
                            return this.semuaUker.filter(u => String(u.kode_spv) === String(this.kodeIndukTerpilih));
                        }
                    }"
                >
                    @csrf
                    @method('PUT')

                    {{-- STEP 1: pilih uker induk --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">1. Uker Induk</label>
                        <select x-model="kodeIndukTerpilih" @change="ukerKodeTerpilih = ''" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option value="">-- Pilih Uker Induk --</option>
                            <template x-for="induk in daftarInduk" :key="induk.kode_spv">
                                <option :value="induk.kode_spv" x-text="induk.uker_spv"></option>
                            </template>
                        </select>
                    </div>

                    {{-- STEP 2: cabang/unit --}}
                    <div x-show="kodeIndukTerpilih" x-cloak>
                        <label class="block text-sm font-medium text-gray-700">2. Cabang / Unit</label>
                        <select name="uker_kode" x-model="ukerKodeTerpilih" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option value="">-- Pilih Cabang/Unit --</option>
                            <template x-for="anggota in anggotaUker" :key="anggota.kode">
                                <option :value="anggota.kode" x-text="anggota.nama"></option>
                            </template>
                        </select>
                    </div>

                    {{-- STEP 3: Kode Aset resmi (dikelompokkan per kategori) --}}
                    <div x-show="ukerKodeTerpilih" x-cloak>
                        <label class="block text-sm font-medium text-gray-700">3. Kode Aset</label>
                        <select name="kode_aset_kode" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option value="">-- Pilih Kode Aset --</option>
                            @foreach ($kodeAsetList->groupBy('kategori') as $kategori => $daftar)
                                <optgroup label="{{ $kategori }}">
                                    @foreach ($daftar as $ka)
                                        <option value="{{ $ka->kode }}" @selected(old('kode_aset_kode', $aset->kode_aset_kode) == $ka->kode)>{{ $ka->kode }} - {{ $ka->nama }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">ID Aset saat ini: <strong>{{ $aset->no_asset }}</strong> (tidak berubah walau kode aset diedit).</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Merek</label>
                        <input type="text" name="merek" value="{{ old('merek', $aset->merek) }}" class="mt-1 block w-full border-gray-300 rounded-md" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <input type="text" name="tipe_model" value="{{ old('tipe_model', $aset->tipe_model) }}" class="mt-1 block w-full border-gray-300 rounded-md" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Serial Number (SN)</label>
                        <input type="text" name="sn" value="{{ old('sn', $aset->sn) }}" class="mt-1 block w-full border-gray-300 rounded-md" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kapasitas Memori (opsional)</label>
                        <input type="text" name="kapasitas_memori" value="{{ old('kapasitas_memori', $aset->kapasitas_memori) }}" placeholder="contoh: 8GB" class="mt-1 block w-full border-gray-300 rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tahun Distribusi (opsional)</label>
                        <input type="number" name="tahun_perolehan" value="{{ old('tahun_perolehan', $aset->tahun_perolehan) }}" min="2000" max="{{ date('Y') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        <p class="text-xs text-gray-400 mt-1">Kosongkan jika tidak diketahui pasti (misal aset lama tanpa catatan tahun). Umur & status PH dihitung otomatis dari sini.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kondisi (opsional)</label>
                        <select name="kondisi" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option value="">-- Pilih Kondisi --</option>
                            @foreach (\App\Models\Aset::DAFTAR_KONDISI as $k)
                                <option value="{{ $k }}" @selected(old('kondisi', $aset->kondisi) == $k)>{{ $k }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="border-t pt-4 space-y-4">
                        <p class="text-sm font-semibold text-gray-600">Data Pemegang / Perangkat (opsional, isi jika relevan)</p>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama User (Pemegang Perangkat)</label>
                            <input type="text" name="pemegang_nama" value="{{ old('pemegang_nama', $aset->pemegang_nama) }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jabatan</label>
                            <input type="text" name="jabatan" value="{{ old('jabatan', $aset->jabatan) }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Personal Number (PN)</label>
                            <input type="text" name="pemegang_pn" value="{{ old('pemegang_pn', $aset->pemegang_pn) }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">IP Address</label>
                            <input type="text" name="ip_address" value="{{ old('ip_address', $aset->ip_address) }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status Hardening</label>
                            <input type="text" name="status_hardening" value="{{ old('status_hardening', $aset->status_hardening) }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status Bitlocker</label>
                            <input type="text" name="status_bitlocker" value="{{ old('status_bitlocker', $aset->status_bitlocker) }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status DLP</label>
                            <input type="text" name="status_dlp" value="{{ old('status_dlp', $aset->status_dlp) }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status Antivirus</label>
                            <input type="text" name="status_antivirus" value="{{ old('status_antivirus', $aset->status_antivirus) }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Keterangan (opsional)</label>
                        <textarea name="keterangan" class="mt-1 block w-full border-gray-300 rounded-md" rows="3" placeholder="contoh: Lantai 21">{{ old('keterangan', $aset->keterangan) }}</textarea>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Simpan</button>
                        <a href="{{ route('aset.index') }}" class="px-4 py-2 rounded border">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
