<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Health Check</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
            @endif

            <div class="mb-4 flex flex-wrap gap-2">
                <a href="{{ route('healthcheck.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                    + Buat Form Health Check
                </a>
                <a href="{{ route('healthcheck.bulkUploadForm') }}" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                    Upload Massal (Excel)
                </a>
                <a href="{{ route('healthcheck.bulkDeleteForm') }}" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                    Delete Massal (Excel)
                </a>
                <a href="{{ route('healthcheck.export.excel', request()->query()) }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Export Excel
                </a>
                <a href="{{ route('healthcheck.export.pdf', request()->query()) }}" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                    Export PDF
                </a>
            </div>

            <form method="GET" action="{{ route('healthcheck.index') }}" class="mb-4 bg-white p-4 rounded-lg shadow-sm flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Cari Periode</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="contoh: Juli 2026" class="block w-full border-gray-300 rounded-md text-sm">
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
                        <a href="{{ route('healthcheck.index') }}" class="px-4 py-2 rounded border text-sm">Reset</a>
                    @endif
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Uker</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Periode</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Tanggal</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Compliance</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Tindak Lanjut</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Approval</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($formList as $form)
                            @php $persen = $form->persenCompliance(); @endphp
                            <tr>
                                <td class="px-4 py-2">{{ $form->uker?->nama }}</td>
                                <td class="px-4 py-2">{{ $form->periode }}</td>
                                <td class="px-4 py-2">{{ $form->tanggal_pemeriksaan }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded font-semibold
                                        {{ $persen >= 95 ? 'bg-green-100 text-green-700' : ($persen >= 80 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                        {{ $persen }}%
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded
                                        {{ $form->status_tindak_lanjut === 'Selesai Diperbaiki' ? 'bg-green-100 text-green-700' : ($form->status_tindak_lanjut === 'Sedang Diproses' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') }}">
                                        {{ $form->status_tindak_lanjut }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded font-semibold
                                        {{ match($form->status_approval) {
                                            'Disetujui' => 'bg-green-100 text-green-700',
                                            'Menunggu Approval' => 'bg-yellow-100 text-yellow-700',
                                            'Ditolak' => 'bg-red-100 text-red-700',
                                            default => 'bg-gray-100 text-gray-600',
                                        } }}">
                                        {{ $form->status_approval }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 space-x-2 whitespace-nowrap">
                                    <a href="{{ route('healthcheck.edit', $form) }}" class="text-indigo-600">Isi/Edit</a>
                                    <form action="{{ route('healthcheck.destroy', $form) }}" method="POST" class="inline" onsubmit="return confirm('Hapus form ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-4 text-center text-gray-500">Belum ada form health check.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $formList->links() }}</div>
        </div>
    </div>
</x-app-layout>
