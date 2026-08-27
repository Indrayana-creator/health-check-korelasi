<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Kelola Uker / Cabang</h2>
    </x-slot>

    <div class="p-7 space-y-4 max-w-[1360px]">

        <x-page-tabs :tabs="[
            ['label' => 'Kelola User', 'href' => route('users.index'), 'active' => false],
            ['label' => 'Kelola Uker', 'href' => route('ukers.index'), 'active' => true],
            ['label' => 'Kelola Kode Aset', 'href' => route('kode-aset.index'), 'active' => false],
            ['label' => 'Kelola Pekerja', 'href' => route('pekerja.index'), 'active' => false],
            ['label' => 'Permintaan Edit', 'href' => route('aset.editRequests.index'), 'active' => false],
            ['label' => 'Log History', 'href' => route('log-history.index'), 'active' => false],
        ]" />

        <x-flash-status />

        <div class="flex flex-wrap gap-2 items-end">
            <x-button :href="route('ukers.create')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 5v14M5 12h14"></path></svg>
                Tambah Uker/Cabang
            </x-button>
            <form method="GET" class="flex gap-2">
                <x-text-input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama/kode uker..." />
                <x-button type="submit">Cari</x-button>
            </form>
            <div class="flex gap-2 ml-auto">
                <x-button variant="secondary" :href="route('ukers.export.excel')">Export Excel</x-button>
                <x-button variant="secondary" :href="route('ukers.export.pdf')">Export PDF</x-button>
            </div>
        </div>

        <x-table-scroll-hint />
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Kode</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Nama</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Jenis</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Cabang Induk</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Alamat</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($ukers as $u)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $u->kode }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-800">{{ $u->nama }}</td>
                            <td class="px-4 py-3">
                                <x-badge color="gray">{{ $u->jenis }}</x-badge>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $u->uker_spv }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ Str::limit($u->alamat, 40) ?: '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <x-icon-button variant="edit" label="Edit" :href="route('ukers.edit', $u)">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    </x-icon-button>
                                    <form action="{{ route('ukers.destroy', $u) }}" method="POST" class="inline" onsubmit="return confirm('Hapus uker ini?')">
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
                            <td colspan="6" class="px-4 py-10 text-center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1M9 13h1M14 9h1M14 13h1M9 21v-4h6v4"></path></svg>
                                @if (request('q'))
                                    <p class="text-gray-400 text-sm mb-3">Gak ada uker yang cocok dengan pencarian ini.</p>
                                    <x-button variant="secondary" size="sm" :href="route('ukers.index')">Reset Pencarian</x-button>
                                @else
                                    <p class="text-gray-400 text-sm mb-3">Belum ada data uker.</p>
                                    <x-button size="sm" :href="route('ukers.create')">Tambah Uker/Cabang</x-button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $ukers->links() }}</div>
    </div>
</x-app-layout>
