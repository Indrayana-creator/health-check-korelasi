<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Kelola User</h2>
    </x-slot>

    <div class="p-7 space-y-4 max-w-[1360px]">

        <x-page-tabs :tabs="[
            ['label' => 'Kelola User', 'href' => route('users.index'), 'active' => true],
            ['label' => 'Kelola Uker', 'href' => route('ukers.index'), 'active' => false],
            ['label' => 'Kelola Kode Aset', 'href' => route('kode-aset.index'), 'active' => false],
            ['label' => 'Kelola Pekerja', 'href' => route('pekerja.index'), 'active' => false],
            ['label' => 'Permintaan Edit', 'href' => route('aset.editRequests.index'), 'active' => false],
            ['label' => 'Log History', 'href' => route('log-history.index'), 'active' => false],
        ]" />

        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">{{ session('status') }}</div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-cakrawala/10 text-cakrawala flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Total User</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($totalUser, 0, ',', '.') }}</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-nusantara/10 text-nusantara flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M9 12l2 2 4-4M5 6h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Administrator</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($totalAdmin, 0, ',', '.') }}</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-green-100 text-green-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1M9 13h1M14 9h1M14 13h1M9 21v-4h6v4"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Petugas Cabang</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($totalPetugas, 0, ',', '.') }}</p>
            </x-card>
        </div>

        <div class="flex justify-end gap-2">
            <x-button variant="secondary" :href="route('users.export.excel')">Export Excel</x-button>
            <x-button variant="secondary" :href="route('users.export.pdf')">Export PDF</x-button>
            <x-button :href="route('users.create')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 5v14M5 12h14"></path></svg>
                Tambah User
            </x-button>
        </div>

        <x-table-scroll-hint />
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Nama</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">PN</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Role</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Uker</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center text-[11px] font-bold flex-none">
                                        {{ collect(explode(' ', $user->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                                    </div>
                                    <span class="text-sm font-semibold text-gray-800">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm font-mono text-gray-600">{{ $user->pn ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="$user->role === 'admin' ? 'nusantara' : 'gray'">{{ $user->role }}</x-badge>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $user->ukerRelasi?->nama ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="$user->is_active ? 'green' : 'gray'">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    @if ($user->id !== auth()->id())
                                        <form action="{{ route('users.toggleActive', $user) }}" method="POST" class="inline" onsubmit="return confirm('{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} user ini?')">
                                            @csrf
                                            <x-icon-button :variant="$user->is_active ? 'danger' : 'success'" :label="$user->is_active ? 'Nonaktifkan' : 'Aktifkan'" type="submit">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M18.36 6.64a9 9 0 11-12.73 0M12 2v10"></path></svg>
                                            </x-icon-button>
                                        </form>
                                    @endif
                                    <x-icon-button variant="edit" label="Edit" :href="route('users.edit', $user)">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    </x-icon-button>
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Hapus user ini?')">
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
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8z"></path></svg>
                                <p class="text-gray-400 text-sm mb-3">Belum ada user.</p>
                                <x-button size="sm" :href="route('users.create')">Tambah User</x-button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $users->links() }}</div>
    </div>
</x-app-layout>
