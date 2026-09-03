<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Login History</h2>
        <p class="text-xs text-gray-500 mt-0.5">Jejak audit tiap percobaan login -- berhasil maupun gagal, lengkap sama IP address & perangkat.</p>
    </x-slot>

    <div class="p-7 space-y-5 max-w-[1360px]">

        <x-page-tabs :tabs="[
            ['label' => 'Kelola User', 'href' => route('users.index'), 'active' => false],
            ['label' => 'Kelola Uker', 'href' => route('ukers.index'), 'active' => false],
            ['label' => 'Kelola Kode Aset', 'href' => route('kode-aset.index'), 'active' => false],
            ['label' => 'Kelola Pekerja', 'href' => route('pekerja.index'), 'active' => false],
            ['label' => 'Permintaan Edit', 'href' => route('aset.editRequests.index'), 'active' => false],
            ['label' => 'Log History', 'href' => route('log-history.index'), 'active' => false],
            ['label' => 'Login History', 'href' => route('login-history.index'), 'active' => true],
        ]" />

        <div class="flex justify-end gap-2">
            <x-button variant="secondary" :href="route('login-history.export.excel', request()->query())">Export Excel</x-button>
            <x-button variant="secondary" :href="route('login-history.export.pdf', request()->query())">Export PDF</x-button>
        </div>

        {{-- Ringkasan tahun --}}
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-extrabold text-sm text-gray-800">Ringkasan Tahun {{ $tahunRingkasan }}</h3>
                <form method="GET" class="flex items-center gap-2">
                    <x-select name="tahun" onchange="this.form.submit()">
                        @foreach ($tahunTersedia as $t)
                            <option value="{{ $t }}" @selected($tahunRingkasan == $t)>{{ $t }}</option>
                        @endforeach
                    </x-select>
                </form>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 rounded-xl border border-green-200 bg-green-50">
                    <p class="text-xs text-gray-500">Login Berhasil</p>
                    <p class="text-2xl font-extrabold text-gray-800">{{ $totalBerhasil }}x</p>
                </div>
                <div class="p-4 rounded-xl border border-red-200 bg-red-50">
                    <p class="text-xs text-gray-500">Login Gagal/Ditolak</p>
                    <p class="text-2xl font-extrabold text-gray-800">{{ $totalGagal }}x</p>
                </div>
            </div>
        </x-card>

        {{-- Filter --}}
        <x-card padding="p-4">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="tahun" value="{{ $tahunRingkasan }}">
                <div class="min-w-[220px]">
                    <x-input-label class="text-xs font-semibold text-gray-500 mb-1">Cari PN / Nama User</x-input-label>
                    <x-text-input type="text" name="q" value="{{ request('q') }}" placeholder="Cari PN atau nama..." class="block w-full" />
                </div>
                <div class="min-w-[200px]">
                    <x-input-label class="text-xs font-semibold text-gray-500 mb-1">Filter Status</x-input-label>
                    <x-select name="status" class="block w-full">
                        <option value="">Semua Status</option>
                        @foreach (\App\Models\LoginLog::LABEL_STATUS as $key => $label)
                            <option value="{{ $key }}" @selected(request('status') == $key)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                </div>
                <x-button type="submit">Terapkan</x-button>
            </form>
        </x-card>

        {{-- Daftar log --}}
        <x-table-scroll-hint />
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-cakrawala">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Waktu</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">PN Dicoba</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Nama User</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Status</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">IP Address</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Perangkat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($logs as $log)
                        @php
                            $warnaStatus = $log->status === \App\Models\LoginLog::STATUS_BERHASIL ? 'green' : 'red';
                        @endphp
                        <tr class="hover:bg-cakrawala/5 {{ \App\Support\StatusColor::aksenBorder($warnaStatus) }}">
                            <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $log->pn_dicoba }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-700">{{ $log->user?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="$warnaStatus">{{ \App\Models\LoginLog::LABEL_STATUS[$log->status] ?? $log->status }}</x-badge>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 font-mono text-xs">{{ $log->ip_address }}</td>
                            <td class="px-4 py-3 text-xs text-gray-400 max-w-xs truncate" title="{{ $log->user_agent }}">{{ $log->user_agent }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M12.5 7a4 4 0 11-8 0 4 4 0 018 0zM22 21v-2a4 4 0 00-3-3.87M17 3.13a4 4 0 010 7.75"></path></svg>
                                <p class="text-gray-400 text-sm">Belum ada riwayat login tercatat.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $logs->links() }}</div>
    </div>
</x-app-layout>
