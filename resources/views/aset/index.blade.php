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
            <x-button :href="route('aset.create')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 5v14M5 12h14"></path></svg>
                Tambah Aset
            </x-button>
            <x-button variant="secondary" :href="route('aset.bulkUploadForm')">Upload Massal (Excel)</x-button>
            <x-button variant="secondary" :href="route('aset.bulkDeleteForm')">Delete Massal (Excel)</x-button>
            <x-button variant="secondary" :href="route('aset.export.excel', request()->query())">Export Excel</x-button>
            <x-button variant="secondary" :href="route('aset.export.pdf', request()->query())">Export PDF</x-button>
        </div>

        <x-card padding="p-4">
            <form method="GET" action="{{ route('aset.index') }}" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <x-input-label class="text-xs font-semibold text-gray-500 mb-1">Cari (ASET ID / Merek / Type / SN / Nama User)</x-input-label>
                    <x-text-input type="text" name="q" value="{{ request('q') }}" class="block w-full" />
                </div>
                @if ($ukerFilterList->isNotEmpty())
                    <div class="min-w-[200px]">
                        <x-input-label class="text-xs font-semibold text-gray-500 mb-1">Filter Uker</x-input-label>
                        <x-select name="uker_kode" class="block w-full">
                            <option value="">Semua Uker</option>
                            @foreach ($ukerFilterList as $u)
                                <option value="{{ $u->kode }}" @selected(request('uker_kode') == $u->kode)>{{ $u->nama }}</option>
                            @endforeach
                        </x-select>
                    </div>
                @endif
                <div class="flex gap-2">
                    <x-button type="submit">Terapkan</x-button>
                    @if (request('q') || request('uker_kode'))
                        <x-button variant="secondary" :href="route('aset.index')">Reset</x-button>
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
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $aset->no_asset }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $aset->uker?->nama }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $aset->kode_aset_kode }} - {{ $aset->kodeAset?->nama }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $aset->merek }} {{ $aset->tipe_model }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $aset->sn }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                @if ($aset->tahun_perolehan)
                                    {{ $aset->umur_tahun }} thn
                                    @if ($aset->sudah_ph)
                                        <x-badge color="red" class="ml-1">PH</x-badge>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $aset->kondisi }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $aset->pemegang_nama }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <x-icon-button variant="edit" label="Edit" :href="route('aset.edit', $aset)">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    </x-icon-button>
                                    <form action="{{ route('aset.destroy', $aset) }}" method="POST" class="inline" onsubmit="return confirm('Hapus aset ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-icon-button variant="danger" label="Hapus" type="submit">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"></path></svg>
                                        </x-icon-button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M3 8l9-5 9 5-9 5-9-5zM3 8v8l9 5 9-5V8M12 13v8"></path></svg>
                                <p class="text-gray-400 text-sm">Belum ada data aset.</p>
                            </td>
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
