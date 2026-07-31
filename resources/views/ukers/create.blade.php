<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Tambah Uker / Cabang Baru</h2>
    </x-slot>

    <div class="p-7">
        <div class="max-w-xl mx-auto">
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

                <form action="{{ route('ukers.store') }}" method="POST" class="space-y-4" x-data="{ kodeSpvTerpilih: '{{ old('kode_spv') }}' }">
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Kode Uker</label>
                        <input type="number" name="kode" value="{{ old('kode') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" required>
                        <p class="text-xs text-gray-400 mt-1.5">Kode unik, harus beda dari uker yang sudah ada.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Nama Uker/Cabang</label>
                        <input type="text" name="nama" value="{{ old('nama') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Jenis</label>
                        <select name="jenis" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" required>
                            <option value="">-- Pilih Jenis --</option>
                            @foreach (\App\Http\Controllers\UkerController::DAFTAR_JENIS as $j)
                                <option value="{{ $j }}" @selected(old('jenis') == $j)>{{ $j }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-uker-combobox
                        name="kode_spv"
                        label="Cabang Induk (Supervisi)"
                        :daftar-uker="$ukerIndukList->map(fn($u) => ['kode' => $u->kode, 'nama' => $u->nama])->toJson()"
                        model-value="kodeSpvTerpilih"
                        placeholder="Ketik nama atau kode cabang induk..."
                    />
                    <p class="text-xs text-gray-400 -mt-2">Kalau ini uker level Kanwil paling atas, pilih dirinya sendiri sebagai induk.</p>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Alamat (opsional)</label>
                        <textarea name="alamat" rows="3" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">{{ old('alamat') }}</textarea>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="bg-cakrawala text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-nusantara">Simpan</button>
                        <a href="{{ route('ukers.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50">Batal</a>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
