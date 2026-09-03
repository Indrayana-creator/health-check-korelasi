<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Tambah Uker / Cabang Baru</h2>
    </x-slot>

    <div class="p-7">
        <div class="max-w-xl mx-auto">
            <x-card padding="p-6">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
                        Ada beberapa isian yang perlu diperbaiki, cek keterangan di bawah tiap field.
                    </div>
                @endif

                <form action="{{ route('ukers.store') }}" method="POST" class="space-y-4" x-data="{ kodeSpvTerpilih: '{{ old('kode_spv') }}' }">
                    @csrf

                    <div>
                        <x-input-label value="Kode Uker" required />
                        <x-text-input type="number" name="kode" value="{{ old('kode') }}" class="mt-1.5 block w-full" required />
                        <p class="text-xs text-gray-400 mt-1.5">Kode unik, harus beda dari uker yang sudah ada.</p>
                        <x-input-error :messages="$errors->get('kode')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="Nama Uker/Cabang" required />
                        <x-text-input type="text" name="nama" value="{{ old('nama') }}" class="mt-1.5 block w-full" required />
                        <x-input-error :messages="$errors->get('nama')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="Jenis" required />
                        <x-select name="jenis" class="mt-1.5 block w-full" required>
                            <option value="">-- Pilih Jenis --</option>
                            @foreach (\App\Http\Controllers\UkerController::DAFTAR_JENIS as $j)
                                <option value="{{ $j }}" @selected(old('jenis') == $j)>{{ $j }}</option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('jenis')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-uker-combobox
                            name="kode_spv"
                            label="Cabang Induk (Supervisi)"
                            :daftar-uker="$ukerIndukList->map(fn($u) => ['kode' => $u->kode, 'nama' => $u->nama])->toJson()"
                            model-value="kodeSpvTerpilih"
                            placeholder="Ketik nama atau kode cabang induk..."
                            required
                        />
                        <p class="text-xs text-gray-400 mt-1.5">Kalau ini uker level Kanwil paling atas, pilih dirinya sendiri sebagai induk.</p>
                        <x-input-error :messages="$errors->get('kode_spv')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="Alamat" required />
                        <textarea name="alamat" rows="3" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" required>{{ old('alamat') }}</textarea>
                        <x-input-error :messages="$errors->get('alamat')" class="mt-1.5" />
                    </div>

                    <div class="flex gap-2 pt-2">
                        <x-button type="submit" class="px-5 py-2.5">Simpan</x-button>
                        <x-button variant="secondary" :href="route('ukers.index')" class="px-5 py-2.5">Batal</x-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
