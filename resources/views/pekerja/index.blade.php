<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Kelola Pekerja</h2>
    </x-slot>

    <div class="p-7 space-y-4 max-w-[1360px]">

        <x-page-tabs :tabs="[
            ['label' => 'Kelola User', 'href' => route('users.index'), 'active' => false],
            ['label' => 'Kelola Uker', 'href' => route('ukers.index'), 'active' => false],
            ['label' => 'Kelola Kode Aset', 'href' => route('kode-aset.index'), 'active' => false],
            ['label' => 'Kelola Pekerja', 'href' => route('pekerja.index'), 'active' => true],
            ['label' => 'Permintaan Edit', 'href' => route('aset.editRequests.index'), 'active' => false],
            ['label' => 'Log History', 'href' => route('log-history.index'), 'active' => false],
            ['label' => 'Login History', 'href' => route('login-history.index'), 'active' => false],
        ]" />

        <x-flash-status />

        <p class="text-sm text-gray-500 max-w-3xl">
            Master data pekerja (PN, nama, uker) yang dipakai buat validasi PN saat bikin akun User & isi PIC di form
            Health Check. Buat data massal, pakai menu Import; halaman ini buat tambah/ubah satuan.
        </p>

        <div class="flex flex-wrap gap-2 items-end">
            <x-button :href="route('pekerja.create')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 5v14M5 12h14"></path></svg>
                Tambah Pekerja
            </x-button>
            <form method="GET" class="flex gap-2">
                <x-text-input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau PN..." />
                <x-button type="submit">Cari</x-button>
            </form>
            <div class="flex gap-2 ml-auto">
                <x-button variant="secondary" :href="route('pekerja.export.excel')">Export Excel</x-button>
                <x-button variant="secondary" :href="route('pekerja.export.pdf')">Export PDF</x-button>
            </div>
        </div>

        <x-table-scroll-hint />
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-cakrawala">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">PN</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Nama</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Jabatan</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Uker</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">No HP</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Petugas IT</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($pekerjaList as $p)
                        <tr class="hover:bg-cakrawala/5">
                            <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $p->pn }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-800">{{ $p->nama }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $p->jabatan ?: '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $p->uker?->nama ?: '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                @if ($p->whatsapp_url)
                                    <a href="{{ $p->whatsapp_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-green-600 hover:text-green-700 hover:underline font-semibold">
                                        <svg viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.39 1.26 4.81L2 22l5.44-1.35a9.9 9.9 0 004.6 1.13h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.85 9.85 0 0012.04 2zm5.8 14.1c-.24.68-1.4 1.3-1.93 1.35-.5.06-1.02.27-3.4-.71-2.87-1.19-4.7-4.1-4.85-4.29-.14-.19-1.16-1.55-1.16-2.95 0-1.4.73-2.08.99-2.37.26-.28.57-.35.76-.35.19 0 .38 0 .55.01.18.01.42-.07.65.5.24.58.82 2 .89 2.14.07.14.12.31.02.5-.09.19-.14.31-.28.47-.14.16-.29.36-.42.48-.14.14-.28.29-.12.57.16.28.71 1.17 1.52 1.9 1.05.94 1.93 1.23 2.21 1.37.28.14.44.12.6-.07.16-.19.68-.79.86-1.06.18-.28.36-.23.6-.14.24.09 1.55.73 1.82.86.27.14.44.21.51.32.07.12.07.68-.17 1.36z"></path></svg>
                                        {{ $p->no_hp }}
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($p->is_petugas_it)
                                    <x-badge color="green">Ya</x-badge>
                                @else
                                    <x-badge color="gray">-</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <x-icon-button variant="edit" label="Edit" :href="route('pekerja.edit', $p)">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    </x-icon-button>
                                    <form action="{{ route('pekerja.destroy', $p) }}" method="POST" class="inline" onsubmit="return confirm('Hapus pekerja ini?')">
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
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M12.5 7a4 4 0 11-8 0 4 4 0 018 0zM22 21v-2a4 4 0 00-3-3.87M17 3.13a4 4 0 010 7.75"></path></svg>
                                @if (request('q'))
                                    <p class="text-gray-400 text-sm mb-3">Gak ada pekerja yang cocok dengan pencarian ini.</p>
                                    <x-button variant="secondary" size="sm" :href="route('pekerja.index')">Reset Pencarian</x-button>
                                @else
                                    <p class="text-gray-400 text-sm mb-3">Belum ada data pekerja.</p>
                                    <x-button size="sm" :href="route('pekerja.create')">Tambah Pekerja</x-button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $pekerjaList->links() }}</div>
    </div>
</x-app-layout>
