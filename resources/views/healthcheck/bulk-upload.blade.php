<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Upload Massal Form Health Check</h2>
    </x-slot>

    <div class="p-7">
        <div class="max-w-2xl mx-auto">
            <x-card padding="p-6">

                @if (session('formatSalah'))
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
                        <p class="font-bold mb-1 text-sm">Format file tidak sesuai template.</p>
                        <p class="text-sm">Susunan kolom di file kamu berbeda dari template resmi. Silakan
                        <a href="{{ route('healthcheck.downloadTemplate') }}" class="underline font-semibold">download template Excel</a>
                        di bawah, isi datanya di file itu (jangan ubah urutan/nama kolom header), lalu upload ulang.</p>
                    </div>
                @endif
                @if (session('status'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">{{ session('status') }}</div>
                @endif
                @if (session('gagal') && count(session('gagal')) > 0)
                    <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl text-sm">
                        <p class="font-bold mb-1">Baris yang gagal/dilewati:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach (session('gagal') as $g)
                                <li>{{ $g }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">{{ $errors->first() }}</div>
                @endif

                <div class="mb-4 text-sm text-gray-600 bg-gray-50 p-4 rounded-xl">
                    <p class="font-bold mb-2">Format kolom Excel (baris 1 = header, data mulai baris 2):</p>
                    <p>A: uker_kode &middot; B: tanggal_pemeriksaan (format YYYY-MM-DD) &middot; C: periode &middot; D: pic_pn (opsional, harus PN yang sudah ada di data pekerja)</p>
                    <p class="mt-2 text-xs text-gray-400">Setiap baris akan jadi 1 form baru, otomatis berisi 61 item checklist dengan status awal "Belum Diperiksa".</p>
                    <a href="{{ route('healthcheck.downloadTemplate') }}" class="inline-block mt-3 text-cakrawala font-semibold text-sm hover:underline">&darr; Download Template Excel</a>
                </div>

                <form action="{{ route('healthcheck.bulkUpload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="file" name="file" accept=".xlsx,.xls" required class="block w-full text-sm rounded-lg border-gray-300 focus:border-cakrawala focus:ring-cakrawala">
                    <div class="flex gap-2">
                        <x-button type="submit" class="px-5 py-2.5">Upload &amp; Proses</x-button>
                        <x-button variant="secondary" :href="route('healthcheck.index')" class="px-5 py-2.5">Batal</x-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
