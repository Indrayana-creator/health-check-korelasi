<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Data Aset
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="mb-4 flex flex-wrap gap-2">
                <a href="{{ route('aset.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                    + Tambah Aset
                </a>
                <a href="{{ route('aset.bulkUploadForm') }}" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                    Upload Massal (Excel)
                </a>
                <a href="{{ route('aset.bulkDeleteForm') }}" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                    Delete Massal (Excel)
                </a>
                <a href="{{ route('aset.export.excel', request()->query()) }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Export Excel
                </a>
                <a href="{{ route('aset.export.pdf', request()->query()) }}" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                    Export PDF
                </a>
            </div>

            <form method="GET" action="{{ route('aset.index') }}" class="mb-4 bg-white p-4 rounded-lg shadow-sm flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Cari (ASET ID / Merek / Type / SN / Nama User)</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="block w-full border-gray-300 rounded-md text-sm">
                </div>
                @if ($ukerFilterList->isNotEmpty())
                    <div class="min-w-[200px]">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Filter Uker</label>
                        <select name="uker_kode" class="block w-full border-gray-300 rounded-md text-sm">
                            <option value="">Semua Uker</option>
                            @foreach ($ukerFilterList as $u)
                                <option value="{{ $u->kode }}" @selected(request('uker_kode') == $u->kode)>{{ $u->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="flex gap-2">
                    <button type="submit" class="bg-gray-700 text-white px-4 py-2 rounded text-sm hover:bg-gray-800">Terapkan</button>
                    @if (request('q') || request('uker_kode'))
                        <a href="{{ route('aset.index') }}" class="px-4 py-2 rounded border text-sm">Reset</a>
                    @endif
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">ASET ID</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Uker</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Kode Aset</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Merek / Type</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">SN</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Umur</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Kondisi</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Nama User</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($asetList as $aset)
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">{{ $aset->no_asset }}</td>
                                <td class="px-4 py-2">{{ $aset->uker?->nama }}</td>
                                <td class="px-4 py-2">{{ $aset->kode_aset_kode }} - {{ $aset->kodeAset?->nama }}</td>
                                <td class="px-4 py-2">{{ $aset->merek }} {{ $aset->tipe_model }}</td>
                                <td class="px-4 py-2">{{ $aset->sn }}</td>
                                <td class="px-4 py-2">
                                    @if ($aset->tahun_perolehan)
                                        {{ $aset->umur_tahun }} thn
                                        @if ($aset->sudah_ph)
                                            <span class="ml-1 px-1.5 py-0.5 text-xs rounded bg-red-100 text-red-700">PH</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ $aset->kondisi }}</td>
                                <td class="px-4 py-2">{{ $aset->pemegang_nama }}</td>
                                <td class="px-4 py-2 space-x-2 whitespace-nowrap">
                                    <a href="{{ route('aset.edit', $aset) }}" class="text-indigo-600">Edit</a>
                                    <form action="{{ route('aset.destroy', $aset) }}" method="POST" class="inline" onsubmit="return confirm('Hapus aset ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-4 text-center text-gray-500">Belum ada data aset.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $asetList->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
