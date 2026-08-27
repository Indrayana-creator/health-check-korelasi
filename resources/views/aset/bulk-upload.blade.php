<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Upload Massal Data Aset</h2>
    </x-slot>

    <div class="p-7">
        <div class="max-w-2xl mx-auto">
            <x-card padding="p-6">

                @if (session('formatSalah'))
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
                        <p class="font-bold mb-1 text-sm">Format file tidak sesuai template.</p>
                        <p class="text-sm">Susunan kolom di file kamu berbeda dari template resmi. Silakan
                        <a href="{{ route('aset.downloadTemplate') }}" class="underline font-semibold">download template Excel</a>
                        di bawah, isi datanya di file itu (jangan ubah urutan/nama kolom header), lalu upload ulang.</p>
                    </div>
                @endif
                <x-flash-status class="mb-4" />
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
                    <p>
                        A: uker_kode &middot;
                        B: kode_aset_kode (contoh: A1, B2, C3 &mdash; sesuai master Kode Aset) &middot;
                        C: merek &middot; D: tipe_model &middot; E: sn &middot;
                        F: no_asset (opsional, kalau kosong di-generate otomatis format Z5-K-uker-kode-urutan) &middot;
                        G: kapasitas_memori &middot; H: tahun_perolehan &middot;
                        I: kondisi (NORMAL/NON DATABASE/PH-DISMANTEL/RUSAK/BACKUP/SERVICE CENTER/TIDAK DIGUNAKAN/TIDAK LAYAK) &middot;
                        J: pemegang_nama &middot; K: jabatan &middot; L: pemegang_pn &middot; M: ip_address &middot;
                        N: status_hardening &middot; O: status_bitlocker &middot; P: status_dlp &middot;
                        Q: status_antivirus &middot; R: keterangan
                    </p>
                    <p class="mt-2 text-xs font-semibold text-red-600">Kolom C-I wajib diisi untuk semua jenis aset. Kolom J-Q wajib diisi khusus untuk kategori Personal Computer, Notebook, Tablet, dan Layar Monitor (kategori lain seperti UPS/Switch/Panel boleh dikosongkan). Kolom F (no_asset) dan R (keterangan) selalu opsional.</p>
                    <a href="{{ route('aset.downloadTemplate') }}" class="inline-block mt-3 text-cakrawala font-semibold text-sm hover:underline">&darr; Download Template Excel</a>
                </div>

                <form action="{{ route('aset.bulkUpload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="file" name="file" accept=".xlsx,.xls" required class="block w-full text-sm rounded-lg border-gray-300 focus:border-cakrawala focus:ring-cakrawala">
                    <div class="flex gap-2">
                        <x-button type="submit" class="px-5 py-2.5">Upload &amp; Proses</x-button>
                        <x-button variant="secondary" :href="route('aset.index')" class="px-5 py-2.5">Batal</x-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
