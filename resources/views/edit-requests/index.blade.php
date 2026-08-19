<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Permintaan Edit Aset</h2>
    </x-slot>

    <div class="p-7 space-y-4 max-w-[1360px]">

        <x-page-tabs :tabs="[
            ['label' => 'Kelola User', 'href' => route('users.index'), 'active' => false],
            ['label' => 'Kelola Uker', 'href' => route('ukers.index'), 'active' => false],
            ['label' => 'Kelola Kode Aset', 'href' => route('kode-aset.index'), 'active' => false],
            ['label' => 'Kelola Pekerja', 'href' => route('pekerja.index'), 'active' => false],
            ['label' => 'Permintaan Edit', 'href' => route('aset.editRequests.index'), 'active' => true],
            ['label' => 'Log History', 'href' => route('log-history.index'), 'active' => false],
        ]" />

        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">{{ session('status') }}</div>
        @endif

        <div class="flex justify-end gap-2">
            <x-button variant="secondary" :href="route('aset.editRequests.export.excel')">Export Excel</x-button>
            <x-button variant="secondary" :href="route('aset.editRequests.export.pdf')">Export PDF</x-button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-orange-100 text-orange-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Menunggu</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($totalMenunggu, 0, ',', '.') }}</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-green-100 text-green-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M20 6L9 17l-5-5"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Disetujui</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($totalDisetujui, 0, ',', '.') }}</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-cakrawala/10 text-cakrawala flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M3 8l9-5 9 5-9 5-9-5zM3 8v8l9 5 9-5V8M12 13v8"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Total Permintaan</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($totalKeseluruhan, 0, ',', '.') }}</p>
            </x-card>
        </div>

        <x-table-scroll-hint />
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
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $r->aset->no_asset }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $r->aset->uker?->nama }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $r->requester?->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $r->alasan ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="match($r->status) { 'Disetujui' => 'green', 'Menunggu' => 'yellow', 'Ditolak' => 'red', default => 'gray' }">
                                    {{ $r->status }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                @if ($r->status === 'Menunggu')
                                    <div x-data="{ open: false }" class="inline-flex items-center gap-1.5">
                                        <form action="{{ route('aset.editRequests.approve', $r) }}" method="POST" class="inline">
                                            @csrf
                                            <x-icon-button variant="success" label="Approve" type="submit">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M20 6L9 17l-5-5"></path></svg>
                                            </x-icon-button>
                                        </form>
                                        <x-icon-button variant="danger" label="Tolak" type="button" @click="open = true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                                        </x-icon-button>

                                        <div x-show="open" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="open = false">
                                            <div class="bg-white p-6 rounded-2xl max-w-md w-full text-left">
                                                <h3 class="font-extrabold text-sm text-gray-800 mb-3">Alasan Penolakan</h3>
                                                <form action="{{ route('aset.editRequests.reject', $r) }}" method="POST">
                                                    @csrf
                                                    <textarea name="catatan_admin" rows="3" class="w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala mb-3" required></textarea>
                                                    <div class="flex gap-2">
                                                        <x-button variant="danger" type="submit">Tolak &amp; Kirim</x-button>
                                                        <x-button variant="secondary" type="button" @click="open = false">Batal</x-button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <x-icon-button variant="neutral" label="Lihat Aset" :href="route('aset.edit', $r->aset)">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </x-icon-button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                                <p class="text-gray-400 text-sm">Belum ada permintaan edit.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $requests->links() }}</div>
    </div>
</x-app-layout>
