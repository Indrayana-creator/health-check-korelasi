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
                    Setelah disimpan, 61 item pemeriksaan resmi (kategori A-D) akan otomatis dibuat dengan status awal "Belum Diperiksa", dan Anda akan diarahkan ke halaman pengisian.
                </p>

                <form action="{{ route('healthcheck.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label value="Uker" required />
                        <x-select name="uker_kode" class="mt-1.5 block w-full" required>
                            <option value="">-- Pilih Uker --</option>
                            @foreach ($ukerList as $uker)
                                <option value="{{ $uker->kode }}" @selected(old('uker_kode') == $uker->kode)>{{ $uker->nama }}</option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('uker_kode')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="Tanggal Pemeriksaan" required />
                        <x-text-input type="date" name="tanggal_pemeriksaan" value="{{ old('tanggal_pemeriksaan') }}" class="mt-1.5 block w-full" required />
                        <x-input-error :messages="$errors->get('tanggal_pemeriksaan')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="Periode" required />
                        <x-text-input type="text" name="periode" value="{{ old('periode') }}" placeholder="contoh: Juli 2026" class="mt-1.5 block w-full" required />
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
