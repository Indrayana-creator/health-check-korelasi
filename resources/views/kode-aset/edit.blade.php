<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Edit Kode Aset</h2>
    </x-slot>

    <div class="p-7">
        <div class="max-w-xl mx-auto">
            <x-card padding="p-6">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
                        Ada beberapa isian yang perlu diperbaiki, cek keterangan di bawah tiap field.
                    </div>
                @endif

                <form action="{{ route('kode-aset.update', $kodeAset) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label value="Kode" required />
                        <x-text-input type="text" name="kode" value="{{ old('kode', $kodeAset->kode) }}" class="mt-1.5 block w-full" required />
                        <p class="text-xs text-gray-400 mt-1.5">Kode unik, dipakai sebagai identifier di form aset & template upload massal.</p>
                        <x-input-error :messages="$errors->get('kode')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="Kategori" required />
                        <x-text-input type="text" name="kategori" value="{{ old('kategori', $kodeAset->kategori) }}" list="kategori-tersedia" class="mt-1.5 block w-full" required />
                        <datalist id="kategori-tersedia">
                            @foreach ($kategoriTersedia as $kat)
                                <option value="{{ $kat }}"></option>
                            @endforeach
                        </datalist>
                        <p class="text-xs text-gray-400 mt-1.5">Pakai penulisan yang sama dengan kategori yang sudah ada supaya grafik distribusi di dashboard gak kepecah.</p>
                        <x-input-error :messages="$errors->get('kategori')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="Nama Perangkat" required />
                        <x-text-input type="text" name="nama" value="{{ old('nama', $kodeAset->nama) }}" class="mt-1.5 block w-full" required />
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
