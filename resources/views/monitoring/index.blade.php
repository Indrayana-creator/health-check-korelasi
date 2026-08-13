<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Monitoring Kendala</h2>
        <p class="text-xs text-gray-500 mt-0.5">{{ $items->count() }} item bermasalah ditemukan</p>
    </x-slot>

    <div class="p-7 space-y-4 max-w-[1360px]">

        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">{{ session('status') }}</div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500 max-w-3xl">
                @if ($isAdmin)
                    Daftar semua item checklist Health Check yang berstatus "Not OK" dari seluruh uker, biar bisa dipantau
                    uker mana yang lagi ada kendala dan kenapa -- tanpa harus buka form satu-satu.
                @else
                    Daftar item checklist Health Check yang berstatus "Not OK" dari uker Anda dan seluruh cabang di
                    bawahnya, biar bisa dipantau dan ditindaklanjuti tanpa harus buka form satu-satu.
                @endif
            </p>
            <div class="flex gap-2 flex-none">
                <x-button variant="secondary" :href="route('monitoring.export.excel', request()->query())">Export Excel</x-button>
                <x-button variant="secondary" :href="route('monitoring.export.pdf', request()->query())">Export PDF</x-button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-red-100 text-red-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Total Bermasalah</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ $totalBermasalah }}</p>
            </x-card>
            <x-card padding="p-5" :class="$totalMendesak > 0 ? '!border-red-200' : ''">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-red-100 text-red-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M12 8v4l3 3M12 22a10 10 0 100-20 10 10 0 000 20z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Mendesak (&gt;{{ \App\Http\Controllers\MonitoringController::AMBANG_HARI_MENDESAK }} hari)</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ $totalMendesak }}</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-gray-100 text-gray-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M12 8v4l3 3M12 22a10 10 0 100-20 10 10 0 000 20z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Belum Ditindaklanjuti</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ $totalBelum }}</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-green-100 text-green-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M20 6L9 17l-5-5"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Selesai Diperbaiki</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ $totalSelesai }}</p>
            </x-card>
        </div>

        <x-card padding="p-3.5">
            <form method="GET" action="{{ route('monitoring.index') }}" class="flex flex-wrap gap-3 items-center">
                <x-uker-filter-combobox
                    name="uker_kode"
                    :daftar-uker="$ukerFilterList->map(fn ($u) => ['kode' => $u->kode, 'nama' => $u->nama])->toJson()"
                    :selected="request('uker_kode')"
                    :initial-label="$ukerFilterList->firstWhere('kode', request('uker_kode'))?->nama"
                    class="min-w-[190px]"
                />
                <x-select name="kategori" class="min-w-[220px]">
                    <option value="">Semua Kategori</option>
                    @foreach ($kategoriList as $k)
                        <option value="{{ $k }}" @selected(request('kategori') == $k)>{{ $k }}</option>
                    @endforeach
                </x-select>
                <x-select name="status_tindak_lanjut" class="min-w-[190px]">
                    <option value="">Semua Status Tindak Lanjut</option>
                    @foreach (\App\Models\HealthCheckForm::DAFTAR_STATUS_TINDAK_LANJUT as $s)
                        <option value="{{ $s }}" @selected(request('status_tindak_lanjut') == $s)>{{ $s }}</option>
                    @endforeach
                </x-select>
                <div class="flex gap-2">
                    <x-button type="submit">Terapkan</x-button>
                    @if (request('uker_kode') || request('kategori') || request('status_tindak_lanjut'))
                        <x-button variant="secondary" :href="route('monitoring.index')">Reset</x-button>
                    @endif
                </div>
            </form>
        </x-card>

        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Uker</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Kategori</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Item Bermasalah</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Catatan</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Periode</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Tindak Lanjut</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($items as $item)
                        @php
                            $mendesak = $item->status_tindak_lanjut === 'Belum Ditindaklanjuti' && \App\Http\Controllers\MonitoringController::itemMendesak($item);
                            $melewatiSla = $item->status_tindak_lanjut === 'Sedang Diproses' && \App\Http\Controllers\MonitoringController::itemMelewatiSlaDiproses($item);
                        @endphp
                        <tr class="hover:bg-gray-50 {{ ($mendesak || $melewatiSla) ? 'border-l-2 border-l-red-400' : '' }}" x-data="{ open: false, riwayatOpen: false }">
                            <td class="px-4 py-3 text-sm font-semibold text-gray-700">{{ $item->form?->uker?->nama }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $item->kategori }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 max-w-xs">
                                {{ $item->item_pemeriksaan }}
                                @if ($mendesak)
                                    <span class="inline-flex items-center gap-1 ml-1.5 px-1.5 py-0.5 rounded-md bg-red-50 text-red-600 text-[10px] font-bold uppercase tracking-wide align-middle">
                                        Mendesak &middot; {{ (int) floor($item->form->tanggal_pemeriksaan->diffInDays(now())) }} hari
                                    </span>
                                @elseif ($melewatiSla)
                                    <span class="inline-flex items-center gap-1 ml-1.5 px-1.5 py-0.5 rounded-md bg-red-50 text-red-600 text-[10px] font-bold uppercase tracking-wide align-middle">
                                        Melewati SLA &middot; Melewati batas {{ \App\Http\Controllers\MonitoringController::hariLewatSlaDiproses($item) }} hari
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 max-w-xs">{{ $item->catatan ?: '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $item->form?->periode }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="match($item->status_tindak_lanjut) { 'Selesai Diperbaiki' => 'green', 'Sedang Diproses' => 'yellow', default => 'gray' }">
                                    {{ $item->status_tindak_lanjut }}
                                </x-badge>
                                @if ($item->catatan_tindak_lanjut)
                                    <p class="text-[11px] text-gray-400 mt-1 max-w-[180px]">{{ $item->catatan_tindak_lanjut }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="inline-flex gap-1.5">
                                    <x-icon-button type="button" variant="neutral" label="Lihat Riwayat" @click="riwayatOpen = true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 8v4l3 3M12 22a10 10 0 100-20 10 10 0 000 20z"></path></svg>
                                    </x-icon-button>
                                    <x-icon-button type="button" variant="edit" label="Update Tindak Lanjut" @click="open = true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    </x-icon-button>
                                </div>

                                <div x-show="open" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="open = false">
                                    <div class="bg-white p-6 rounded-2xl max-w-md w-full text-left">
                                        <h3 class="font-extrabold text-sm text-gray-800 mb-1">Update Tindak Lanjut</h3>
                                        <p class="text-xs text-gray-400 mb-3.5">{{ $item->item_pemeriksaan }} &middot; {{ $item->form?->uker?->nama }}</p>
                                        <form action="{{ route('monitoring.updateTindakLanjut', $item) }}" method="POST" class="space-y-3">
                                            @csrf
                                            <div>
                                                <x-input-label value="Status" />
                                                <x-select name="status_tindak_lanjut" class="mt-1.5 block w-full">
                                                    @foreach (\App\Models\HealthCheckForm::DAFTAR_STATUS_TINDAK_LANJUT as $s)
                                                        <option value="{{ $s }}" @selected($item->status_tindak_lanjut === $s)>{{ $s }}</option>
                                                    @endforeach
                                                </x-select>
                                            </div>
                                            <div>
                                                <x-input-label value="Catatan (opsional)" />
                                                <textarea name="catatan_tindak_lanjut" rows="3" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" placeholder="contoh: sudah diajukan perbaikan ke vendor, estimasi selesai 3 hari">{{ $item->catatan_tindak_lanjut }}</textarea>
                                            </div>
                                            <div class="flex gap-2 pt-1">
                                                <x-button type="submit">Simpan</x-button>
                                                <x-button type="button" variant="secondary" @click="open = false">Batal</x-button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div x-show="riwayatOpen" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 whitespace-normal" @click.self="riwayatOpen = false">
                                    <div class="bg-white p-6 rounded-2xl max-w-4xl w-full text-left">
                                        <h3 class="font-extrabold text-sm text-gray-800 mb-1">Riwayat Tindak Lanjut &mdash; {{ $item->item_pemeriksaan }}</h3>
                                        <p class="text-xs text-gray-400 mb-3.5">{{ $item->form?->uker?->nama }}</p>

                                        @if ($item->statusLogs->isEmpty())
                                            <p class="text-sm text-gray-400 py-6 text-center">Belum ada riwayat perubahan.</p>
                                        @else
                                            <div class="max-h-96 overflow-y-auto">
                                                <table class="w-full table-fixed divide-y divide-gray-100 text-sm">
                                                    <thead class="bg-gray-50 sticky top-0">
                                                        <tr>
                                                            <th class="w-36 px-3 py-2 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Status</th>
                                                            <th class="w-36 px-3 py-2 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Waktu</th>
                                                            <th class="w-48 px-3 py-2 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">User</th>
                                                            <th class="px-3 py-2 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Catatan</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        @foreach ($item->statusLogs as $log)
                                                            <tr>
                                                                <td class="px-3 py-2.5">
                                                                    <x-badge :color="match($log->status) { 'Selesai Diperbaiki' => 'green', 'Sedang Diproses' => 'yellow', default => 'gray' }">
                                                                        {{ $log->status }}
                                                                    </x-badge>
                                                                </td>
                                                                <td class="px-3 py-2.5 text-gray-600">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</td>
                                                                <td class="px-3 py-2.5 text-gray-600 break-words">{{ $log->changedBy?->pn }} &middot; {{ $log->changedBy?->name ?? 'Pengguna terhapus' }}</td>
                                                                <td class="px-3 py-2.5 text-gray-500 break-words">{{ $log->catatan ?: '-' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif

                                        <div class="pt-4">
                                            <x-button type="button" variant="secondary" @click="riwayatOpen = false">Tutup</x-button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M20 6L9 17l-5-5"></path></svg>
                                <p class="text-gray-400 text-sm">Gak ada item bermasalah -- semua aman.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
