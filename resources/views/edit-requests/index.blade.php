<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Permintaan Edit Aset</h2>
    </x-slot>

    <div class="p-7 space-y-4">

        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">{{ session('status') }}</div>
        @endif

        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Aset</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Uker</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Diajukan Oleh</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Alasan</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($requests as $r)
                        <tr>
                            <td class="px-4 py-2.5 font-mono text-xs text-gray-700">{{ $r->aset->no_asset }}</td>
                            <td class="px-4 py-2.5 text-sm text-gray-700">{{ $r->aset->uker?->nama }}</td>
                            <td class="px-4 py-2.5 text-sm text-gray-700">{{ $r->requester?->name }}</td>
                            <td class="px-4 py-2.5 text-sm text-gray-500">{{ $r->alasan ?: '-' }}</td>
                            <td class="px-4 py-2.5">
                                <x-badge :color="match($r->status) { 'Disetujui' => 'green', 'Menunggu' => 'yellow', 'Ditolak' => 'red', default => 'gray' }">
                                    {{ $r->status }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap text-right">
                                @if ($r->status === 'Menunggu')
                                    <form action="{{ route('aset.editRequests.approve', $r) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 text-sm font-semibold mr-2">Approve</button>
                                    </form>
                                    <button type="button" onclick="document.getElementById('modal-tolak-{{ $r->id }}').classList.remove('hidden')" class="text-red-600 text-sm font-semibold">Tolak</button>

                                    <div id="modal-tolak-{{ $r->id }}" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                                        <div class="bg-white p-6 rounded-2xl max-w-md w-full text-left">
                                            <h3 class="font-extrabold text-sm text-gray-800 mb-3">Alasan Penolakan</h3>
                                            <form action="{{ route('aset.editRequests.reject', $r) }}" method="POST">
                                                @csrf
                                                <textarea name="catatan_admin" rows="3" class="w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala mb-3" required></textarea>
                                                <div class="flex gap-2">
                                                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-red-700">Tolak &amp; Kirim</button>
                                                    <button type="button" onclick="document.getElementById('modal-tolak-{{ $r->id }}').classList.add('hidden')" class="px-4 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50">Batal</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @else
                                    <a href="{{ route('aset.edit', $r->aset) }}" class="text-cakrawala text-sm font-semibold">Lihat Aset</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400 text-sm">Belum ada permintaan edit.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $requests->links() }}</div>
    </div>
</x-app-layout>
