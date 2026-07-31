<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            Data Aset
        </h2>
    </x-slot>

    <div class="p-7 space-y-4">

        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('aset.create') }}" class="bg-cakrawala text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-nusantara inline-flex items-center gap-1.5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 5v14M5 12h14"></path></svg>
                Tambah Aset
            </a>
            <a href="{{ route('aset.bulkUploadForm') }}" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-50">
                Upload Massal (Excel)
            </a>
            <a href="{{ route('aset.bulkDeleteForm') }}" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-50">
                Delete Massal (Excel)
            </a>
            <a href="{{ route('aset.export.excel', request()->query()) }}" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-50">
                Export Excel
            </a>
            <a href="{{ route('aset.export.pdf', request()->query()) }}" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-50">
                Export PDF
            </a>
        </div>

        <x-card padding="p-4">
            <form method="GET" action="{{ route('aset.index') }}" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Cari (ASET ID / Merek / Type / SN / Nama User)</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="block w-full border-gray-300 rounded-lg text-sm">
                </div>
                @if ($ukerFilterList->isNotEmpty())
                    <div class="min-w-[200px]">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Filter Uker</label>
                        <select name="uker_kode" class="block w-full border-gray-300 rounded-lg text-sm">
                            <option value="">Semua Uker</option>
                            @foreach ($ukerFilterList as $u)
                                <option value="{{ $u->kode }}" @selected(request('uker_kode') == $u->kode)>{{ $u->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="flex gap-2">
                    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-900">Terapkan</button>
                    @if (request('q') || request('uker_kode'))
                        <a href="{{ route('aset.index') }}" class="px-4 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50">Reset</a>
                    @endif
                </div>
            </form>
        </x-card>

        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">ASET ID</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Uker</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Kode Aset</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Merek / Type</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">SN</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Umur</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Kondisi</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Nama User</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($asetList as $aset)
                        <tr>
                            <td class="px-4 py-2.5 font-mono text-xs text-gray-700">{{ $aset->no_asset }}</td>
                            <td class="px-4 py-2.5 text-sm text-gray-700">{{ $aset->uker?->nama }}</td>
                            <td class="px-4 py-2.5 text-sm text-gray-700">{{ $aset->kode_aset_kode }} - {{ $aset->kodeAset?->nama }}</td>
                            <td class="px-4 py-2.5 text-sm text-gray-700">{{ $aset->merek }} {{ $aset->tipe_model }}</td>
                            <td class="px-4 py-2.5 text-sm text-gray-700">{{ $aset->sn }}</td>
                            <td class="px-4 py-2.5 text-sm text-gray-700">
                                @if ($aset->tahun_perolehan)
                                    {{ $aset->umur_tahun }} thn
                                    @if ($aset->sudah_ph)
                                        <x-badge color="red" class="ml-1">PH</x-badge>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-sm text-gray-700">{{ $aset->kondisi }}</td>
                            <td class="px-4 py-2.5 text-sm text-gray-700">{{ $aset->pemegang_nama }}</td>
                            <td class="px-4 py-2.5 space-x-2 whitespace-nowrap text-right">
                                <a href="{{ route('aset.edit', $aset) }}" class="text-cakrawala text-sm font-semibold">Edit</a>
                                <form action="{{ route('aset.destroy', $aset) }}" method="POST" class="inline" onsubmit="return confirm('Hapus aset ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 text-sm font-semibold">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-gray-400 text-sm">Belum ada data aset.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $asetList->links() }}
        </div>
    </div>
</x-app-layout>
