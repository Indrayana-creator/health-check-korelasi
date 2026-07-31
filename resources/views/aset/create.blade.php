<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            Tambah Aset
        </h2>
    </x-slot>

    <div class="p-7">
        <div class="max-w-2xl mx-auto">
            <x-card padding="p-6">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    action="{{ route('aset.store') }}"
                    method="POST"
                    class="space-y-4"
                    x-data="{
                        ukerKodeTerpilih: '{{ old('uker_kode') }}',
                        perangkatTerpilih: '{{ old('perangkat') }}',
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

                    {{-- STEP 1: cari & pilih uker langsung (combobox pencarian, gak perlu 2 tahap lagi) --}}
                    <x-uker-combobox
                        name="uker_kode"
                        label="1. Uker"
                        :daftar-uker="$ukerList->map(fn($u) => ['kode' => $u->kode, 'nama' => $u->nama])->toJson()"
                        model-value="ukerKodeTerpilih"
                        placeholder="Ketik nama atau kode uker..."
                    />

                    {{-- STEP 2: Kode Aset resmi (dikelompokkan per kategori) --}}
                    <div x-show="ukerKodeTerpilih" x-cloak>
                        <label class="block text-sm font-semibold text-gray-700">2. Kode Aset</label>
                        <select name="kode_aset_kode" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">
                            <option value="">-- Pilih Kode Aset --</option>
                            @foreach ($kodeAsetList->groupBy('kategori') as $kategori => $daftar)
                                <optgroup label="{{ $kategori }}">
                                    @foreach ($daftar as $ka)
                                        <option value="{{ $ka->kode }}" @selected(old('kode_aset_kode') == $ka->kode)>{{ $ka->kode }} - {{ $ka->nama }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1.5">ID Aset (format Z5-K-uker-kode-urutan) akan digenerate otomatis setelah disimpan.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Merek</label>
                        <input type="text" name="merek" value="{{ old('merek') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Type</label>
                        <input type="text" name="tipe_model" value="{{ old('tipe_model') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Serial Number (SN)</label>
                        <input type="text" name="sn" value="{{ old('sn') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Kapasitas Memori (opsional)</label>
                        <input type="text" name="kapasitas_memori" value="{{ old('kapasitas_memori') }}" placeholder="contoh: 8GB" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Tahun Distribusi (opsional)</label>
                        <input type="number" name="tahun_perolehan" value="{{ old('tahun_perolehan') }}" min="2000" max="{{ date('Y') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">
                        <p class="text-xs text-gray-400 mt-1.5">Kosongkan jika tidak diketahui pasti (misal aset lama tanpa catatan tahun). Umur & status PH dihitung otomatis dari sini.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Kondisi (opsional)</label>
                        <select name="kondisi" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">
                            <option value="">-- Pilih Kondisi --</option>
                            @foreach (\App\Models\Aset::DAFTAR_KONDISI as $k)
                                <option value="{{ $k }}" @selected(old('kondisi') == $k)>{{ $k }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="border-t border-gray-100 pt-4 space-y-4">
                        <p class="text-sm font-bold text-gray-600">Data Pemegang / Perangkat (opsional, isi jika relevan)</p>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700">Nama User (Pemegang Perangkat)</label>
                            <input type="text" name="pemegang_nama" value="{{ old('pemegang_nama') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700">Jabatan</label>
                            <input type="text" name="jabatan" value="{{ old('jabatan') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700">Personal Number (PN)</label>
                            <input type="text" name="pemegang_pn" value="{{ old('pemegang_pn') }}" @change="cariUkerDariPn($event.target.value)" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">
                            <p class="text-xs mt-1.5" :class="statusLookupPn.startsWith('Ditemukan') ? 'text-green-600' : 'text-gray-400'" x-text="statusLookupPn"></p>
                            <p class="text-xs text-gray-400">Isi PN dulu biar Uker di atas otomatis terpilih (opsional, tetap bisa dipilih manual).</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700">IP Address</label>
                            <input type="text" name="ip_address" value="{{ old('ip_address') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700">Status Hardening</label>
                            <input type="text" name="status_hardening" value="{{ old('status_hardening') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700">Status Bitlocker</label>
                            <input type="text" name="status_bitlocker" value="{{ old('status_bitlocker') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700">Status DLP</label>
                            <input type="text" name="status_dlp" value="{{ old('status_dlp') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700">Status Antivirus</label>
                            <input type="text" name="status_antivirus" value="{{ old('status_antivirus') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Keterangan (opsional)</label>
                        <textarea name="keterangan" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" rows="3" placeholder="contoh: Lantai 21">{{ old('keterangan') }}</textarea>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="bg-cakrawala text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-nusantara">Simpan</button>
                        <a href="{{ route('aset.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50">Batal</a>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
