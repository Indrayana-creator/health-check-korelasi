<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Health Check</h2>
    </x-slot>

    <div class="p-7 space-y-4">

        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">{{ session('status') }}</div>
        @endif

        <div class="flex flex-wrap gap-2">
            <x-button :href="route('healthcheck.create')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 5v14M5 12h14"></path></svg>
                Buat Form Health Check
            </x-button>
            <x-button variant="secondary" :href="route('healthcheck.bulkUploadForm')">Upload Massal (Excel)</x-button>
            <x-button variant="secondary" :href="route('healthcheck.bulkDeleteForm')">Delete Massal (Excel)</x-button>
            <x-button variant="secondary" :href="route('healthcheck.export.excel', request()->query())">Export Excel</x-button>
            <x-button variant="secondary" :href="route('healthcheck.export.pdf', request()->query())">Export PDF</x-button>
        </div>

        <x-card padding="p-4">
            <form method="GET" action="{{ route('healthcheck.index') }}" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <x-input-label class="text-xs font-semibold text-gray-500 mb-1">Cari Periode</x-input-label>
                    <x-text-input type="text" name="q" value="{{ request('q') }}" placeholder="contoh: Juli 2026" class="block w-full" />
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
                        <x-button variant="secondary" :href="route('healthcheck.index')">Reset</x-button>
                    @endif
                </div>
            </form>
        </x-card>

        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Uker</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Periode</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Tanggal</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Compliance</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Tindak Lanjut</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Approval</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($formList as $form)
                        @php $persen = $form->persenCompliance(); @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-semibold text-gray-700">{{ $form->uker?->nama }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $form->periode }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $form->tanggal_pemeriksaan }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="$persen >= 95 ? 'green' : ($persen >= 80 ? 'yellow' : 'red')">
                                    {{ $persen }}%
                                </x-badge>
                            </td>
                            <td class="px-4 py-3">
                                <x-badge :color="$form->status_tindak_lanjut === 'Selesai Diperbaiki' ? 'green' : ($form->status_tindak_lanjut === 'Sedang Diproses' ? 'yellow' : 'gray')">
                                    {{ $form->status_tindak_lanjut }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3">
                                <x-badge :color="match($form->status_approval) { 'Disetujui' => 'green', 'Menunggu Approval' => 'yellow', 'Ditolak' => 'red', default => 'gray' }">
                                    {{ $form->status_approval }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <x-icon-button variant="edit" label="Isi/Edit" :href="route('healthcheck.edit', $form)">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    </x-icon-button>
                                    <form action="{{ route('healthcheck.destroy', $form) }}" method="POST" class="inline" onsubmit="return confirm('Hapus form ini?')">
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
                            <td colspan="7" class="px-4 py-10 text-center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M9 12l2 2 4-4M5 6h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"></path></svg>
                                <p class="text-gray-400 text-sm">Belum ada form health check.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $formList->links() }}</div>
    </div>
</x-app-layout>
