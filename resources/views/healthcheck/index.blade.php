<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Health Check</h2>
    </x-slot>

    <div class="p-7 space-y-4">

        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">{{ session('status') }}</div>
        @endif

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('healthcheck.create') }}" class="bg-cakrawala text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-nusantara inline-flex items-center gap-1.5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 5v14M5 12h14"></path></svg>
                Buat Form Health Check
            </a>
            <a href="{{ route('healthcheck.bulkUploadForm') }}" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-50">
                Upload Massal (Excel)
            </a>
            <a href="{{ route('healthcheck.bulkDeleteForm') }}" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-50">
                Delete Massal (Excel)
            </a>
            <a href="{{ route('healthcheck.export.excel', request()->query()) }}" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-50">
                Export Excel
            </a>
            <a href="{{ route('healthcheck.export.pdf', request()->query()) }}" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-50">
                Export PDF
            </a>
        </div>

        <x-card padding="p-4">
            <form method="GET" action="{{ route('healthcheck.index') }}" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Cari Periode</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="contoh: Juli 2026" class="block w-full border-gray-300 rounded-lg text-sm">
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
                        <a href="{{ route('healthcheck.index') }}" class="px-4 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50">Reset</a>
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
                        <tr>
                            <td class="px-4 py-2.5 text-sm font-semibold text-gray-700">{{ $form->uker?->nama }}</td>
                            <td class="px-4 py-2.5 text-sm text-gray-700">{{ $form->periode }}</td>
                            <td class="px-4 py-2.5 text-sm text-gray-700">{{ $form->tanggal_pemeriksaan }}</td>
                            <td class="px-4 py-2.5">
                                <x-badge :color="$persen >= 95 ? 'green' : ($persen >= 80 ? 'yellow' : 'red')">
                                    {{ $persen }}%
                                </x-badge>
                            </td>
                            <td class="px-4 py-2.5">
                                <x-badge :color="$form->status_tindak_lanjut === 'Selesai Diperbaiki' ? 'green' : ($form->status_tindak_lanjut === 'Sedang Diproses' ? 'yellow' : 'gray')">
                                    {{ $form->status_tindak_lanjut }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-2.5">
                                <x-badge :color="match($form->status_approval) { 'Disetujui' => 'green', 'Menunggu Approval' => 'yellow', 'Ditolak' => 'red', default => 'gray' }">
                                    {{ $form->status_approval }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-2.5 space-x-2 whitespace-nowrap text-right">
                                <a href="{{ route('healthcheck.edit', $form) }}" class="text-cakrawala text-sm font-semibold">Isi/Edit</a>
                                <form action="{{ route('healthcheck.destroy', $form) }}" method="POST" class="inline" onsubmit="return confirm('Hapus form ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 text-sm font-semibold">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400 text-sm">Belum ada form health check.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $formList->links() }}</div>
    </div>
</x-app-layout>
