<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Health Check</h2>
        <p class="text-xs text-gray-500 mt-0.5">{{ $formList->total() }} dari {{ $totalKeseluruhan }} form ditampilkan</p>
    </x-slot>

    <div class="p-7 space-y-4 max-w-[1360px]">

        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">{{ session('status') }}</div>
        @endif

        <div class="flex flex-wrap gap-2 items-center">
            <x-button :href="route('healthcheck.create')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 5v14M5 12h14"></path></svg>
                Buat Form Health Check
            </x-button>
            <x-dropdown align="left" width="48">
                <x-slot name="trigger">
                    <button type="button" class="flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-200 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50">
                        Kelola Massal
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><path d="M6 9l6 6 6-6"></path></svg>
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link :href="route('healthcheck.bulkUploadForm')">Upload Massal (Excel)</x-dropdown-link>
                    <x-dropdown-link :href="route('healthcheck.bulkDeleteForm')" class="!text-red-600">Delete Massal (Excel)</x-dropdown-link>
                </x-slot>
            </x-dropdown>
            <x-dropdown align="left" width="48">
                <x-slot name="trigger">
                    <button type="button" class="flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-200 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50">
                        Export
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><path d="M6 9l6 6 6-6"></path></svg>
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link :href="route('healthcheck.export.excel', request()->query())">Excel (.xlsx)</x-dropdown-link>
                    <x-dropdown-link :href="route('healthcheck.export.pdf', request()->query())">PDF</x-dropdown-link>
                </x-slot>
            </x-dropdown>
            <x-button variant="secondary" :href="route('healthcheck.trash')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"></path></svg>
                Sampah
            </x-button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-nusantara/10 text-nusantara flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M9 12l2 2 4-4M5 6h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Total Form</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ $totalKeseluruhan }}</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-green-100 text-green-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M9 12l2 2 4-4M12 22a10 10 0 100-20 10 10 0 000 20z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Rata-rata Compliance</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ $avgCompliance }}%</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-orange-100 text-orange-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Menunggu Approval</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ $totalMenunggu }}</p>
            </x-card>
        </div>

        <x-card padding="p-3.5">
            <form method="GET" action="{{ route('healthcheck.index') }}" class="flex flex-wrap gap-3 items-center">
                <div class="relative flex-1 min-w-[220px]">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4-4"></path></svg>
                    <x-text-input type="text" name="q" value="{{ request('q') }}" placeholder="Cari periode, contoh: Juli 2026" class="block w-full !pl-9" />
                </div>
                @if ($ukerFilterList->isNotEmpty())
                    <x-select name="uker_kode" class="min-w-[190px]">
                        <option value="">Semua Uker</option>
                        @foreach ($ukerFilterList as $u)
                            <option value="{{ $u->kode }}" @selected(request('uker_kode') == $u->kode)>{{ $u->nama }}</option>
                        @endforeach
                    </x-select>
                @endif
                <div class="flex gap-1.5 flex-wrap">
                    @foreach ([null => 'Semua', 'Menunggu Approval' => 'Menunggu Approval', 'Disetujui' => 'Disetujui', 'Ditolak' => 'Ditolak'] as $value => $label)
                        @php $aktif = request('status_approval') == $value; @endphp
                        <a
                            href="{{ route('healthcheck.index', array_merge(request()->except(['status_approval', 'page']), $value ? ['status_approval' => $value] : [])) }}"
                            class="px-3 py-1.5 rounded-full border text-xs font-bold whitespace-nowrap {{ $aktif ? 'bg-cakrawala text-white border-cakrawala' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}"
                        >{{ $label }}</a>
                    @endforeach
                </div>
                <div class="flex gap-2">
                    <x-button type="submit">Terapkan</x-button>
                    @if (request('q') || request('uker_kode') || request('status_approval'))
                        <x-button variant="secondary" :href="route('healthcheck.index')">Reset</x-button>
                    @endif
                </div>
            </form>
        </x-card>

        <x-compliance-legend />

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
                                <x-badge :color="\App\Support\ComplianceScale::badgeColor($persen)">
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
