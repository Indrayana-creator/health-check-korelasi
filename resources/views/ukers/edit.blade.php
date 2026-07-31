<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Uker / Cabang</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
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

                <form action="{{ route('ukers.update', $uker) }}" method="POST" class="space-y-4" x-data="{ kodeSpvTerpilih: '{{ old('kode_spv', $uker->kode_spv) }}' }">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kode Uker</label>
                        <input type="number" name="kode" value="{{ old('kode', $uker->kode) }}" readonly class="mt-1 block w-full border-gray-300 rounded-md" required>
                        <p class="text-xs text-gray-400 mt-1">Kode unik, harus beda dari uker yang sudah ada.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Uker/Cabang</label>
                        <input type="text" name="nama" value="{{ old('nama', $uker->nama) }}" class="mt-1 block w-full border-gray-300 rounded-md" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jenis</label>
                        <select name="jenis" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            <option value="">-- Pilih Jenis --</option>
                            @foreach (\App\Http\Controllers\UkerController::DAFTAR_JENIS as $j)
                                <option value="{{ $j }}" @selected(old('jenis', $uker->jenis) == $j)>{{ $j }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-uker-combobox
                        name="kode_spv"
                        label="Cabang Induk (Supervisi)"
                        :daftar-uker="$ukerIndukList->map(fn($u) => ['kode' => $u->kode, 'nama' => $u->nama])->toJson()"
                        initial-label="{{ $uker->uker_spv }}"
                        model-value="kodeSpvTerpilih"
                        placeholder="Ketik nama atau kode cabang induk..."
                    />
                    <p class="text-xs text-gray-400 -mt-2">Kalau ini uker level Kanwil paling atas, pilih dirinya sendiri sebagai induk.</p>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Alamat (opsional)</label>
                        <textarea name="alamat" rows="3" class="mt-1 block w-full border-gray-300 rounded-md">{{ old('alamat', $uker->alamat) }}</textarea>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Simpan</button>
                        <a href="{{ route('ukers.index') }}" class="px-4 py-2 rounded border">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
