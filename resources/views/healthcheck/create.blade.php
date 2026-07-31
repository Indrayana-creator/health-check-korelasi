<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Buat Form Health Check</h2>
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

                <p class="mb-4 text-sm text-gray-500">
                    Setelah disimpan, 61 item pemeriksaan resmi (kategori A-D) akan otomatis dibuat dengan status awal "Belum Diperiksa", dan Anda akan diarahkan ke halaman pengisian.
                </p>

                <form action="{{ route('healthcheck.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Uker</label>
                        <select name="uker_kode" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" required>
                            <option value="">-- Pilih Uker --</option>
                            @foreach ($ukerList as $uker)
                                <option value="{{ $uker->kode }}" @selected(old('uker_kode') == $uker->kode)>{{ $uker->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Tanggal Pemeriksaan</label>
                        <input type="date" name="tanggal_pemeriksaan" value="{{ old('tanggal_pemeriksaan') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Periode</label>
                        <input type="text" name="periode" value="{{ old('periode') }}" placeholder="contoh: Juli 2026" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">PIC (PN Teknisi, opsional)</label>
                        <input type="text" name="pic_pn" value="{{ old('pic_pn') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">
                        <p class="text-xs text-gray-400 mt-1.5">Kosongkan dulu jika data master pekerja belum diimport &mdash; PN yang diisi harus sudah terdaftar di tabel pekerja.</p>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="bg-cakrawala text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-nusantara">Buat &amp; Lanjut Isi</button>
                        <a href="{{ route('healthcheck.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50">Batal</a>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
