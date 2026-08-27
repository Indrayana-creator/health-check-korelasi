<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Buat Form Health Check</h2>
    </x-slot>

    <div class="p-7">
        <div class="max-w-xl mx-auto">
            <x-card padding="p-6">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
                        Ada beberapa isian yang perlu diperbaiki, cek keterangan di bawah tiap field.
                    </div>
                @endif

                <p class="mb-4 text-sm text-gray-500">
                    Setelah disimpan, {{ collect(config('health_check_checklist'))->flatten()->count() }} item pemeriksaan resmi akan otomatis dibuat dengan status awal "Belum Diperiksa", dan Anda akan diarahkan ke halaman pengisian.
                </p>

                @if (request('kategori_tujuan'))
                    <div class="mb-4 p-3 bg-cakrawala/5 border border-cakrawala/20 rounded-xl text-xs text-cakrawala">
                        Habis dibuat, Anda otomatis diarahkan ke tab kategori "{{ request('kategori_tujuan') }}" (dari scan QR Ruangan).
                    </div>
                @endif

                <form action="{{ route('healthcheck.store') }}" method="POST" class="space-y-4" x-data="{ ukerKodeTerpilih: '{{ old('uker_kode', request('uker_kode')) }}' }">
                    @csrf
                    <input type="hidden" name="kategori_tujuan" value="{{ old('kategori_tujuan', request('kategori_tujuan')) }}">

                    <div>
                        <x-uker-combobox
                            name="uker_kode"
                            label="Uker"
                            :daftar-uker="$ukerList->map(fn($u) => ['kode' => $u->kode, 'nama' => $u->nama])->toJson()"
                            model-value="ukerKodeTerpilih"
                            placeholder="Ketik nama atau kode uker..."
                        />
                        <x-input-error :messages="$errors->get('uker_kode')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="Tanggal Pemeriksaan" required />
                        <x-text-input type="date" name="tanggal_pemeriksaan" value="{{ old('tanggal_pemeriksaan') }}" class="mt-1.5 block w-full" required />
                        <x-input-error :messages="$errors->get('tanggal_pemeriksaan')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="Periode" required />
                        <x-text-input type="text" name="periode" value="{{ old('periode', $periodeSaran) }}" placeholder="contoh: 10-14 Agustus 2026" class="mt-1.5 block w-full" required />
                        <p class="text-xs text-gray-400 mt-1.5">Siklus Health Check mingguan (Senin-Jumat) -- rentang tanggal minggu berjalan sudah disarankan otomatis, tapi tetap bisa diubah manual kalau perlu.</p>
                        <x-input-error :messages="$errors->get('periode')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="PIC (PN Teknisi, opsional)" />
                        <x-text-input type="text" name="pic_pn" value="{{ old('pic_pn') }}" class="mt-1.5 block w-full" />
                        <p class="text-xs text-gray-400 mt-1.5">Kosongkan dulu jika data master pekerja belum diimport &mdash; PN yang diisi harus sudah terdaftar di tabel pekerja.</p>
                        <x-input-error :messages="$errors->get('pic_pn')" class="mt-1.5" />
                    </div>

                    <div class="flex gap-2 pt-2">
                        <x-button type="submit" class="px-5 py-2.5">Buat &amp; Lanjut Isi</x-button>
                        <x-button variant="secondary" :href="route('healthcheck.index')" class="px-5 py-2.5">Batal</x-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
