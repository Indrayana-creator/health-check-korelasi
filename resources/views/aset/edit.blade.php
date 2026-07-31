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

                @if (session('status'))
                    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
                @endif

                @if (!$bisaDiedit)
                    <div class="mb-4 p-4 bg-yellow-50 border border-yellow-300 rounded">
                        <p class="font-semibold text-yellow-800 mb-1">Data ini terkunci.</p>

                        @if ($permintaanMenunggu)
                            <p class="text-sm text-yellow-700">Permintaan edit sudah diajukan, menunggu approval admin.</p>
                        @else
                            <p class="text-sm text-yellow-700 mb-3">Ajukan permintaan edit dulu, tunggu admin approve sebelum bisa mengubah data ini.</p>
                            <form action="{{ route('aset.requestEdit', $aset) }}" method="POST" class="space-y-2">
                                @csrf
                                <input type="text" name="alasan" placeholder="Alasan minta edit (opsional)" class="block w-full border-gray-300 rounded-md text-sm">
                                <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded text-sm hover:bg-yellow-700">Ajukan Permintaan Edit</button>
                            </form>
                        @endif
                    </div>
                @endif

                <form
                    action="{{ route('aset.update', $aset) }}"
                    method="POST"
                    class="space-y-4"
                    x-data="{
                        ukerKodeTerpilih: '{{ old('uker_kode', $aset->uker_kode) }}',
                        perangkatTerpilih: '{{ old('perangkat', $aset->perangkat ?? '') }}',
                        statusLookupPn: '',
                        async cariUkerDariPn(pn) {
                            if (!pn) { this.statusLookupPn = ''; return; }
                            this.statusLookupPn = 'Mencari...';
                            try {
                                const res = await fetch(`/api/pekerja-uker/${pn}`);
                                if (!res.ok) { this.statusLookupPn = 'PN tidak ditemukan di data pekerja.'; return; }
                                const data = await res.json();
                                this.ukerKodeTerpilih = String(data.uker_kode);
                                this.statusLookupPn = `Ditemukan: ${data.nama} - ${data.uker_nama}`;
                            } catch (e) {
                                this.statusLookupPn = 'Gagal mencari PN.';
                            }
                        }
                    }"
                >
                    @csrf
                    @method('PUT')

                    {{-- STEP 1: cari & pilih uker langsung (combobox pencarian, gak perlu 2 tahap lagi) --}}
                    <x-uker-combobox
                        name="uker_kode"
                        label="1. Uker"
                        :daftar-uker="$ukerList->map(fn($u) => ['kode' => $u->kode, 'nama' => $u->nama])->toJson()"
                        model-value="ukerKodeTerpilih"
                        placeholder="Ketik nama atau kode uker..."
                        :initial-label="$aset->uker?->nama"
                    />

                    {{-- STEP 2: Kode Aset resmi (dikelompokkan per kategori) --}}
                    <div x-show="ukerKodeTerpilih" x-cloak>
                        <label class="block text-sm font-medium text-gray-700">2. Kode Aset</label>
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
                            <input type="text" name="pemegang_pn" value="{{ old('pemegang_pn', $aset->pemegang_pn) }}" @change="cariUkerDariPn($event.target.value)" class="mt-1 block w-full border-gray-300 rounded-md">
                            <p class="text-xs mt-1" :class="statusLookupPn.startsWith('Ditemukan') ? 'text-green-600' : 'text-gray-400'" x-text="statusLookupPn"></p>
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
                        @if ($bisaDiedit)
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Simpan</button>
                        @endif
                        <a href="{{ route('aset.index') }}" class="px-4 py-2 rounded border">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
