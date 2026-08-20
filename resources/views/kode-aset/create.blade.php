<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Tambah Kode Aset Baru</h2>
    </x-slot>

    <div class="p-7">
        <div class="max-w-xl mx-auto">
            <x-card padding="p-6">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
                        Ada beberapa isian yang perlu diperbaiki, cek keterangan di bawah tiap field.
                    </div>
                @endif

                <form action="{{ route('kode-aset.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label value="Kode" required />
                        <x-text-input type="text" name="kode" value="{{ old('kode') }}" class="mt-1.5 block w-full" required />
                        <p class="text-xs text-gray-400 mt-1.5">Kode unik, dipakai sebagai identifier di form aset & template upload massal.</p>
                        <x-input-error :messages="$errors->get('kode')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="Kategori" required />
                        <x-select name="kategori" class="mt-1.5 block w-full" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach (\App\Models\KodeAset::DAFTAR_KATEGORI as $kat)
                                <option value="{{ $kat }}" @selected(old('kategori') == $kat)>{{ $kat }}</option>
                            @endforeach
                        </x-select>
                        <p class="text-xs text-gray-400 mt-1.5">Dikunci ke daftar tetap -- kategori nentuin field mana yang wajib diisi pas nambah aset, jadi gak boleh beda-beda penulisan.</p>
                        <x-input-error :messages="$errors->get('kategori')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="Nama Perangkat" required />
                        <x-text-input type="text" name="nama" value="{{ old('nama') }}" class="mt-1.5 block w-full" required />
                        <x-input-error :messages="$errors->get('nama')" class="mt-1.5" />
                    </div>

                    <div class="flex gap-2 pt-2">
                        <x-button type="submit" class="px-5 py-2.5">Simpan</x-button>
                        <x-button variant="secondary" :href="route('kode-aset.index')" class="px-5 py-2.5">Batal</x-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
