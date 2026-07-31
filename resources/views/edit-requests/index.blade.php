<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Permintaan Edit Aset</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Aset</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Uker</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Diajukan Oleh</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Alasan</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($requests as $r)
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">{{ $r->aset->no_asset }}</td>
                                <td class="px-4 py-2">{{ $r->aset->uker?->nama }}</td>
                                <td class="px-4 py-2">{{ $r->requester?->name }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $r->alasan ?: '-' }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded font-semibold
                                        {{ match($r->status) {
                                            'Disetujui' => 'bg-green-100 text-green-700',
                                            'Menunggu' => 'bg-yellow-100 text-yellow-700',
                                            'Ditolak' => 'bg-red-100 text-red-700',
                                            default => 'bg-gray-100 text-gray-600',
                                        } }}">
                                        {{ $r->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    @if ($r->status === 'Menunggu')
                                        <form action="{{ route('aset.editRequests.approve', $r) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-green-600 mr-2">Approve</button>
                                        </form>
                                        <button type="button" onclick="document.getElementById('modal-tolak-{{ $r->id }}').classList.remove('hidden')" class="text-red-600">Tolak</button>

                                        <div id="modal-tolak-{{ $r->id }}" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                                            <div class="bg-white p-6 rounded-lg max-w-md w-full">
                                                <h3 class="font-semibold text-gray-800 mb-3">Alasan Penolakan</h3>
                                                <form action="{{ route('aset.editRequests.reject', $r) }}" method="POST">
                                                    @csrf
                                                    <textarea name="catatan_admin" rows="3" class="w-full border-gray-300 rounded-md mb-3" required></textarea>
                                                    <div class="flex gap-2">
                                                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">Tolak &amp; Kirim</button>
                                                        <button type="button" onclick="document.getElementById('modal-tolak-{{ $r->id }}').classList.add('hidden')" class="px-4 py-2 rounded border text-sm">Batal</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @else
                                        <a href="{{ route('aset.edit', $r->aset) }}" class="text-indigo-600 text-sm">Lihat Aset</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-4 text-center text-gray-500">Belum ada permintaan edit.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $requests->links() }}</div>
        </div>
    </div>
</x-app-layout>
