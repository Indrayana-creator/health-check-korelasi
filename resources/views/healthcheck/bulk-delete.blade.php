<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Delete Massal Form Health Check</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">

                @if (session('status'))
                    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
                @endif
                @if (session('gagal') && count(session('gagal')) > 0)
                    <div class="mb-4 p-4 bg-yellow-100 text-yellow-800 rounded text-sm">
                        <p class="font-semibold mb-1">Baris yang tidak ditemukan:</p>
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

                <div class="mb-4 text-sm text-gray-600 bg-red-50 p-4 rounded border border-red-200">
                    <p class="font-semibold mb-1 text-red-700">Perhatian &mdash; aksi ini tidak bisa dibatalkan.</p>
                    <p>Kolom Excel: <strong>A = uker_kode</strong>, <strong>B = periode</strong>. Form yang cocok (beserta seluruh 61 item checklist di dalamnya) akan langsung dihapus permanen.</p>
                </div>

                <form action="{{ route('healthcheck.bulkDelete') }}" method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="return confirm('Yakin mau hapus semua form health check yang cocok di file ini? Aksi ini tidak bisa dibatalkan.')">
                    @csrf
                    <input type="file" name="file" accept=".xlsx,.xls" required class="block w-full">
                    <div class="flex gap-2">
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Upload &amp; Hapus</button>
                        <a href="{{ route('healthcheck.index') }}" class="px-4 py-2 rounded border">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>