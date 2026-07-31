<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Delete Massal Data Aset</h2>
    </x-slot>

    <div class="p-7">
        <div class="max-w-2xl mx-auto">
            <x-card padding="p-6">

                @if (session('status'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">{{ session('status') }}</div>
                @endif
                @if (session('gagal') && count(session('gagal')) > 0)
                    <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl text-sm">
                        <p class="font-bold mb-1">Baris yang tidak ditemukan:</p>
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

                <div class="mb-4 text-sm text-gray-600 bg-red-50 p-4 rounded-xl border border-red-200">
                    <p class="font-bold mb-1 text-red-700">Perhatian &mdash; aksi ini tidak bisa dibatalkan.</p>
                    <p>Kolom Excel: <strong>A = SN</strong> (Serial Number). Setiap aset dengan SN yang cocok akan langsung dihapus permanen.</p>
                </div>

                <form action="{{ route('aset.bulkDelete') }}" method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="return confirm('Yakin mau hapus semua aset yang SN-nya ada di file ini? Aksi ini tidak bisa dibatalkan.')">
                    @csrf
                    <input type="file" name="file" accept=".xlsx,.xls" required class="block w-full text-sm rounded-lg border-gray-300 focus:border-cakrawala focus:ring-cakrawala">
                    <div class="flex gap-2">
                        <button type="submit" class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-700">Upload &amp; Hapus</button>
                        <a href="{{ route('aset.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50">Batal</a>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
