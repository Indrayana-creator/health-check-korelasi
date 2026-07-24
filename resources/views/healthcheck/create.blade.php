<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Buat Form Health Check</h2>
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

                <p class="mb-4 text-sm text-gray-500">
                    Setelah disimpan, 61 item pemeriksaan resmi (kategori A-D) akan otomatis dibuat dengan status awal "Belum Diperiksa", dan Anda akan diarahkan ke halaman pengisian.
                </p>

                <form action="{{ route('healthcheck.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Uker</label>
                        <select name="uker_kode" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            <option value="">-- Pilih Uker --</option>
                            @foreach ($ukerList as $uker)
                                <option value="{{ $uker->kode }}" @selected(old('uker_kode') == $uker->kode)>{{ $uker->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tanggal Pemeriksaan</label>
                        <input type="date" name="tanggal_pemeriksaan" value="{{ old('tanggal_pemeriksaan') }}" class="mt-1 block w-full border-gray-300 rounded-md" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Periode</label>
                        <input type="text" name="periode" value="{{ old('periode') }}" placeholder="contoh: Juli 2026" class="mt-1 block w-full border-gray-300 rounded-md" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">PIC (PN Teknisi, opsional)</label>
                        <input type="text" name="pic_pn" value="{{ old('pic_pn') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        <p class="text-xs text-gray-400 mt-1">Kosongkan dulu jika data master pekerja belum diimport &mdash; PN yang diisi harus sudah terdaftar di tabel pekerja.</p>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Buat &amp; Lanjut Isi</button>
                        <a href="{{ route('healthcheck.index') }}" class="px-4 py-2 rounded border">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
