<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Log History</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Ringkasan tahun --}}
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-800">Ringkasan Tahun {{ $tahunRingkasan }}</h3>
                    <form method="GET" class="flex items-center gap-2">
                        <select name="tahun" onchange="this.form.submit()" class="border-gray-300 rounded-md text-sm">
                            @foreach ($tahunTersedia as $t)
                                <option value="{{ $t }}" @selected($tahunRingkasan == $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                @if ($ringkasan->isEmpty())
                    <p class="text-sm text-gray-400">Belum ada aktivitas tercatat di tahun ini.</p>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @foreach ($ringkasan as $r)
                            <div class="p-4 rounded border {{ $r->aksi === 'delete_massal' ? 'border-red-200 bg-red-50' : 'border-green-200 bg-green-50' }}">
                                <p class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $r->modul)) }} &middot; {{ str_replace('_', ' ', $r->aksi) }}</p>
                                <p class="text-2xl font-bold text-gray-800">{{ $r->jumlah_kejadian }}x</p>
                                <p class="text-xs text-gray-400">{{ $r->total_baris }} baris data</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Filter --}}
            <form method="GET" class="bg-white p-4 rounded-lg shadow-sm flex flex-wrap gap-3 items-end">
                <input type="hidden" name="tahun" value="{{ $tahunRingkasan }}">
                <div class="min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Filter Modul</label>
                    <select name="modul" class="block w-full border-gray-300 rounded-md text-sm">
                        <option value="">Semua Modul</option>
                        <option value="aset" @selected(request('modul') == 'aset')>Aset</option>
                        <option value="health_check" @selected(request('modul') == 'health_check')>Health Check</option>
                        <option value="pekerja_uker" @selected(request('modul') == 'pekerja_uker')>Pekerja/Uker</option>
                    </select>
                </div>
                <button type="submit" class="bg-gray-700 text-white px-4 py-2 rounded text-sm hover:bg-gray-800">Terapkan</button>
            </form>

            {{-- Daftar log --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Waktu</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">User</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Modul</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Aksi</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Jumlah Baris</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="px-4 py-2 text-sm">{{ $log->created_at->format('d M Y H:i') }}</td>
                                <td class="px-4 py-2 text-sm">{{ $log->user?->name }}</td>
                                <td class="px-4 py-2 text-sm">{{ ucfirst(str_replace('_', ' ', $log->modul)) }}</td>
                                <td class="px-4 py-2 text-sm">
                                    <span class="px-2 py-1 text-xs rounded {{ $log->aksi === 'delete_massal' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                        {{ str_replace('_', ' ', $log->aksi) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm">{{ $log->jumlah_baris }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $log->keterangan }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-4 text-center text-gray-500">Belum ada log aktivitas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $logs->links() }}</div>
        </div>
    </div>
</x-app-layout>
