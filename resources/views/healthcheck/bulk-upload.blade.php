<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Upload Massal Form Health Check</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">

                @if (session('status'))
                    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
                @endif
                @if (session('gagal') && count(session('gagal')) > 0)
                    <div class="mb-4 p-4 bg-yellow-100 text-yellow-800 rounded text-sm">
                        <p class="font-semibold mb-1">Baris yang gagal/dilewati:</p>
                        <ul class="list-disc list-inside">
                            @foreach (session('gagal') as $g)
                                <li>{{ $g }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">{{ $errors->first() }}</div>
                @endif

                <div class="mb-4 text-sm text-gray-600 bg-gray-50 p-4 rounded">
                    <p class="font-semibold mb-2">Format kolom Excel (baris 1 = header, data mulai baris 2):</p>
                    <p>A: uker_kode &middot; B: tanggal_pemeriksaan (format YYYY-MM-DD) &middot; C: periode &middot; D: pic_pn (opsional, harus PN yang sudah ada di data pekerja)</p>
                    <p class="mt-2 text-xs text-gray-400">Setiap baris akan jadi 1 form baru, otomatis berisi 61 item checklist dengan status awal "Belum Diperiksa".</p>
                </div>

                <form action="{{ route('healthcheck.bulkUpload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="file" name="file" accept=".xlsx,.xls" required class="block w-full">
                    <div class="flex gap-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Upload &amp; Proses</button>
                        <a href="{{ route('healthcheck.index') }}" class="px-4 py-2 rounded border">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>