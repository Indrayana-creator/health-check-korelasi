<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            Tambah Aset
        </h2>
    </x-slot>

    <div class="p-7">
        <div class="max-w-3xl mx-auto">

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
                    Ada beberapa isian yang perlu diperbaiki, cek keterangan di bawah tiap field.
                </div>
            @endif

            <form
                action="{{ route('aset.store') }}"
                method="POST"
                class="space-y-5"
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

                <x-card padding="p-6">
                    <h3 class="font-extrabold text-sm text-gray-800 mb-4">Identitas Aset</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            {{-- STEP 1: cari & pilih uker langsung (combobox pencarian, gak perlu 2 tahap lagi) --}}
                            <x-uker-combobox
                                name="uker_kode"
                                label="1. Uker"
                                :daftar-uker="$ukerList->map(fn($u) => ['kode' => $u->kode, 'nama' => $u->nama])->toJson()"
                                model-value="ukerKodeTerpilih"
                                placeholder="Ketik nama atau kode uker..."
                            />
                            <x-input-error :messages="$errors->get('uker_kode')" class="mt-1.5" />
                        </div>

                        <div class="md:col-span-2" x-show="ukerKodeTerpilih" x-cloak>
                            <x-input-label value="2. Kode Aset" required />
                            <x-select name="kode_aset_kode" class="mt-1.5 block w-full">
                                <option value="">-- Pilih Kode Aset --</option>
                                @foreach ($kodeAsetList->groupBy('kategori') as $kategori => $daftar)
                                    <optgroup label="{{ $kategori }}">
                                        @foreach ($daftar as $ka)
                                            <option value="{{ $ka->kode }}" @selected(old('kode_aset_kode') == $ka->kode)>{{ $ka->kode }} - {{ $ka->nama }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </x-select>
                            <p class="text-xs text-gray-400 mt-1.5">ID Aset (format Z5-K-uker-kode-urutan) akan digenerate otomatis setelah disimpan.</p>
                            <x-input-error :messages="$errors->get('kode_aset_kode')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label value="Merek" required />
                            <x-text-input type="text" name="merek" value="{{ old('merek') }}" class="mt-1.5 block w-full" required />
                            <x-input-error :messages="$errors->get('merek')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label value="Type" required />
                            <x-text-input type="text" name="tipe_model" value="{{ old('tipe_model') }}" class="mt-1.5 block w-full" required />
                            <x-input-error :messages="$errors->get('tipe_model')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label value="Serial Number (SN)" required />
                            <x-text-input type="text" name="sn" value="{{ old('sn') }}" class="mt-1.5 block w-full" required />
                            <x-input-error :messages="$errors->get('sn')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label value="Kapasitas Memori (opsional)" />
                            <x-text-input type="text" name="kapasitas_memori" value="{{ old('kapasitas_memori') }}" placeholder="contoh: 8GB" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('kapasitas_memori')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label value="Tahun Distribusi (opsional)" />
                            <x-text-input type="number" name="tahun_perolehan" value="{{ old('tahun_perolehan') }}" min="2000" max="{{ date('Y') }}" class="mt-1.5 block w-full" />
                            <p class="text-xs text-gray-400 mt-1.5">Kosongkan jika tidak diketahui pasti. Umur & status PH dihitung otomatis dari sini.</p>
                            <x-input-error :messages="$errors->get('tahun_perolehan')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label value="Kondisi (opsional)" />
                            <x-select name="kondisi" class="mt-1.5 block w-full">
                                <option value="">-- Pilih Kondisi --</option>
                                @foreach (\App\Models\Aset::DAFTAR_KONDISI as $k)
                                    <option value="{{ $k }}" @selected(old('kondisi') == $k)>{{ $k }}</option>
                                @endforeach
                            </x-select>
                            <x-input-error :messages="$errors->get('kondisi')" class="mt-1.5" />
                        </div>
                    </div>
                </x-card>

                <x-card padding="p-6">
                    <h3 class="font-extrabold text-sm text-gray-800 mb-1">Data Pemegang & Keamanan</h3>
                    <p class="text-xs text-gray-400 mb-4">Opsional, isi jika perangkat ini dipegang oleh 1 orang pengguna.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label value="Nama User (Pemegang Perangkat)" />
                            <x-text-input type="text" name="pemegang_nama" value="{{ old('pemegang_nama') }}" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('pemegang_nama')" class="mt-1.5" />
                        </div>
                        <div>
                            <x-input-label value="Jabatan" />
                            <x-text-input type="text" name="jabatan" value="{{ old('jabatan') }}" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('jabatan')" class="mt-1.5" />
                        </div>
                        <div>
                            <x-input-label value="Personal Number (PN)" />
                            <x-text-input type="text" name="pemegang_pn" value="{{ old('pemegang_pn') }}" @change="cariUkerDariPn($event.target.value)" class="mt-1.5 block w-full" />
                            <p class="text-xs mt-1.5" :class="statusLookupPn.startsWith('Ditemukan') ? 'text-green-600' : 'text-gray-400'" x-text="statusLookupPn"></p>
                            <p class="text-xs text-gray-400">Isi PN dulu biar Uker di atas otomatis terpilih (opsional, tetap bisa dipilih manual).</p>
                            <x-input-error :messages="$errors->get('pemegang_pn')" class="mt-1.5" />
                        </div>
                        <div>
                            <x-input-label value="IP Address" />
                            <x-text-input type="text" name="ip_address" value="{{ old('ip_address') }}" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('ip_address')" class="mt-1.5" />
                        </div>
                        <div>
                            <x-input-label value="Status Hardening" />
                            <x-text-input type="text" name="status_hardening" value="{{ old('status_hardening') }}" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('status_hardening')" class="mt-1.5" />
                        </div>
                        <div>
                            <x-input-label value="Status Bitlocker" />
                            <x-text-input type="text" name="status_bitlocker" value="{{ old('status_bitlocker') }}" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('status_bitlocker')" class="mt-1.5" />
                        </div>
                        <div>
                            <x-input-label value="Status DLP" />
                            <x-text-input type="text" name="status_dlp" value="{{ old('status_dlp') }}" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('status_dlp')" class="mt-1.5" />
                        </div>
                        <div>
                            <x-input-label value="Status Antivirus" />
                            <x-text-input type="text" name="status_antivirus" value="{{ old('status_antivirus') }}" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('status_antivirus')" class="mt-1.5" />
                        </div>
                    </div>
                </x-card>

                <x-card padding="p-6">
                    <h3 class="font-extrabold text-sm text-gray-800 mb-4">Keterangan</h3>
                    <x-input-label value="Keterangan (opsional)" />
                    <textarea name="keterangan" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" rows="3" placeholder="contoh: Lantai 21">{{ old('keterangan') }}</textarea>
                    <x-input-error :messages="$errors->get('keterangan')" class="mt-1.5" />
                </x-card>

                <div class="flex gap-2">
                    <x-button type="submit" size="md" class="px-5 py-2.5">Simpan</x-button>
                    <x-button variant="secondary" :href="route('aset.index')" size="md" class="px-5 py-2.5">Batal</x-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
