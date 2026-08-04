<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Log History</h2>
    </x-slot>

    <div class="p-7 space-y-5">

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

            @if ($ringkasan->isEmpty())
                <p class="text-sm text-gray-400">Belum ada aktivitas tercatat di tahun ini.</p>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach ($ringkasan as $r)
                        <div class="p-4 rounded-xl border {{ $r->aksi === 'delete_massal' ? 'border-red-200 bg-red-50' : 'border-green-200 bg-green-50' }}">
                            <p class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $r->modul)) }} &middot; {{ str_replace('_', ' ', $r->aksi) }}</p>
                            <p class="text-2xl font-extrabold text-gray-800">{{ $r->jumlah_kejadian }}x</p>
                            <p class="text-xs text-gray-400">{{ $r->total_baris }} baris data</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        {{-- Filter --}}
        <x-card padding="p-4">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="tahun" value="{{ $tahunRingkasan }}">
                <div class="min-w-[200px]">
                    <x-input-label class="text-xs font-semibold text-gray-500 mb-1">Filter Modul</x-input-label>
                    <x-select name="modul" class="block w-full">
                        <option value="">Semua Modul</option>
                        <option value="aset" @selected(request('modul') == 'aset')>Aset</option>
                        <option value="health_check" @selected(request('modul') == 'health_check')>Health Check</option>
                        <option value="pekerja_uker" @selected(request('modul') == 'pekerja_uker')>Pekerja/Uker</option>
                    </x-select>
                </div>
                <x-button type="submit">Terapkan</x-button>
            </form>
        </x-card>

        {{-- Daftar log --}}
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Waktu</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">User</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Modul</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Aksi</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Jumlah Baris</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-700">{{ $log->user?->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ ucfirst(str_replace('_', ' ', $log->modul)) }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="$log->aksi === 'delete_massal' ? 'red' : 'green'">{{ str_replace('_', ' ', $log->aksi) }}</x-badge>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $log->jumlah_baris }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $log->keterangan }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M12 22a10 10 0 100-20 10 10 0 000 20zM12 6v6l4 2"></path></svg>
                                <p class="text-gray-400 text-sm">Belum ada log aktivitas.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $logs->links() }}</div>
    </div>
</x-app-layout>
