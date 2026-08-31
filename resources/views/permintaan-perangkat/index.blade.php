<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Permintaan Perangkat</h2>
        <p class="text-xs text-gray-500 mt-0.5">{{ $permintaanList->count() }} permintaan ditemukan</p>
    </x-slot>

    <div class="p-7 space-y-4 max-w-[1360px]" x-data="{ ajukanOpen: false, selected: [] }">

        {{-- auto-hide dimatikan -- ada link + tombol salin yang mungkin masih
             mau dipakai user, jangan sampai ke-hide sendiri sebelum sempat disalin. --}}
        <x-flash-status :auto-hide="false">
            {{ session('status') }}
            @if (session('linkLacak'))
                <div class="mt-2 flex items-center gap-2 flex-wrap" x-data="{ copied: false }">
                    <span class="text-xs text-green-700">Link cek status (bisa dibagikan, gak perlu login):</span>
                    <a href="{{ session('linkLacak') }}" target="_blank" class="text-xs font-mono text-cakrawala hover:underline break-all">{{ session('linkLacak') }}</a>
                    <button
                        type="button"
                        @click="navigator.clipboard.writeText('{{ session('linkLacak') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="px-2 py-1 rounded-md border border-green-300 text-[11px] font-semibold text-green-700 hover:bg-green-100"
                    >
                        <span x-show="!copied">Salin Link</span>
                        <span x-show="copied" x-cloak>Tersalin!</span>
                    </button>
                </div>
            @endif
        </x-flash-status>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500 max-w-3xl">
                @if ($isAdmin)
                    Daftar semua permintaan perangkat/perbaikan yang diajukan cabang, biar bisa dipantau dan diupdate statusnya.
                @else
                    Daftar permintaan perangkat/perbaikan yang diajukan uker Anda ke admin.
                @endif
            </p>
            <div class="flex gap-2 flex-none">
                <x-button variant="secondary" :href="route('permintaan-perangkat.export.excel', request()->query())">Export Excel</x-button>
                <x-button variant="secondary" :href="route('permintaan-perangkat.export.pdf', request()->query())">Export PDF</x-button>
                @unless ($isAdmin)
                    <x-button type="button" @click="ajukanOpen = true">Ajukan Permintaan</x-button>
                @endunless
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-cakrawala/10 text-cakrawala flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Total Permintaan</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($totalKeseluruhan, 0, ',', '.') }}</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-orange-100 text-orange-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M12 8v4l3 3M12 22a10 10 0 100-20 10 10 0 000 20z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Masih Berjalan</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($totalPending, 0, ',', '.') }}</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-green-100 text-green-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M20 6L9 17l-5-5"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Done Terkirim</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($totalSelesai, 0, ',', '.') }}</p>
            </x-card>
        </div>

        @if ($isAdmin)
            <x-card padding="p-3.5">
                <form method="GET" action="{{ route('permintaan-perangkat.index') }}" class="flex flex-wrap gap-3 items-center">
                    <x-uker-filter-combobox
                        name="uker_kode"
                        :daftar-uker="$ukerFilterList->map(fn ($u) => ['kode' => $u->kode, 'nama' => $u->nama])->toJson()"
                        :selected="request('uker_kode')"
                        :initial-label="$ukerFilterList->firstWhere('kode', request('uker_kode'))?->nama"
                        class="min-w-[190px]"
                    />
                    <x-select name="status" class="min-w-[190px]">
                        <option value="">Semua Status</option>
                        @foreach (\App\Models\PermintaanPerangkat::DAFTAR_STATUS as $s)
                            <option value="{{ $s }}" @selected(request('status') == $s)>{{ $s }}</option>
                        @endforeach
                    </x-select>
                    <div class="flex gap-2">
                        <x-button type="submit">Terapkan</x-button>
                        @if (request('uker_kode') || request('status'))
                            <x-button variant="secondary" :href="route('permintaan-perangkat.index')">Reset</x-button>
                        @endif
                    </div>
                </form>
            </x-card>
        @endif

        @if ($isAdmin)
            <div x-show="selected.length > 0" x-cloak class="bg-cakrawala/5 border border-cakrawala/30 rounded-2xl px-4 py-3">
                <form
                    action="{{ route('permintaan-perangkat.bulkUpdateStatus') }}"
                    method="POST"
                    class="flex flex-wrap items-center gap-3"
                    @submit="if (!confirm(`Update status ${selected.length} permintaan terpilih?`)) $event.preventDefault()"
                >
                    @csrf
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>
                    <span class="text-sm font-bold text-gray-700" x-text="`${selected.length} permintaan dipilih`"></span>
                    <x-select name="status" class="min-w-[190px]" required>
                        <option value="">-- Ubah status jadi --</option>
                        @foreach (\App\Models\PermintaanPerangkat::DAFTAR_STATUS as $s)
                            <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </x-select>
                    <x-button type="submit" size="sm">Terapkan ke Semua</x-button>
                    <x-button type="button" variant="secondary" size="sm" @click="selected = []">Batal Pilih</x-button>
                </form>
            </div>
        @endif

        <x-table-scroll-hint />
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-cakrawala">
                    <tr>
                        @if ($isAdmin)
                            <th class="px-4 py-2.5 w-8">
                                <input type="checkbox" class="rounded border-gray-300" @change="selected = $event.target.checked ? @js($permintaanList->pluck('id')) : []" :checked="selected.length > 0 && selected.length === {{ $permintaanList->count() }}">
                            </th>
                        @endif
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">No Nota Dinas</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Tanggal Request</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Fungsi Requester</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Jumlah</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Keterangan</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Uker</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Status</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($permintaanList as $p)
                        @php
                            $aksenStatus = match ($p->status) {
                                'Done Terkirim' => 'border-l-[3px] border-l-green-400',
                                'Pending LGA' => 'border-l-[3px] border-l-amber-400',
                                'Pending ESO' => 'border-l-[3px] border-l-blue-400',
                                default => 'border-l-[3px] border-l-transparent',
                            };
                        @endphp
                        <tr class="hover:bg-cakrawala/5 {{ $aksenStatus }}" x-data="{ open: false, riwayatOpen: false, copiedLacak: false }">
                            @if ($isAdmin)
                                <td class="px-4 py-3">
                                    <input type="checkbox" class="rounded border-gray-300" value="{{ $p->id }}" x-model.number="selected">
                                </td>
                            @endif
                            <td class="px-4 py-3 text-sm font-semibold text-gray-800">{{ $p->no_nota_dinas }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $p->tanggal_request->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $p->fungsi_requester }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $p->jumlah }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 max-w-xs">{{ $p->keterangan }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $p->uker?->nama }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="match($p->status) { 'Done Terkirim' => 'green', 'Pending LGA' => 'yellow', 'Pending ESO' => 'blue', default => 'gray' }">
                                    {{ $p->status }}
                                </x-badge>
                                @if ($p->sudahLama())
                                    <x-badge color="red" class="ml-1">{{ $p->hariDiStatusIni() }} hari di status ini</x-badge>
                                @endif
                                @if ($p->catatan_admin)
                                    <p class="text-[11px] text-gray-400 mt-1 max-w-[180px]">{{ $p->catatan_admin }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <x-icon-button type="button" variant="neutral" label="Lihat Riwayat" @click="riwayatOpen = true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 8v4l3 3M12 22a10 10 0 100-20 10 10 0 000 20z"></path></svg>
                                </x-icon-button>
                                <x-icon-button
                                    type="button"
                                    variant="neutral"
                                    label="Salin Link Lacak (bisa dibuka tanpa login)"
                                    @click="navigator.clipboard.writeText('{{ route('permintaan-perangkat.lacak', $p->kode_lacak) }}'); copiedLacak = true; setTimeout(() => copiedLacak = false, 2000)"
                                >
                                    <svg x-show="!copiedLacak" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M9 12h6m-6 4h6m2 4H7a2 2 0 01-2-2V6a2 2 0 012-2h5.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V18a2 2 0 01-2 2z"></path></svg>
                                    <svg x-show="copiedLacak" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 text-green-600"><path d="M20 6L9 17l-5-5"></path></svg>
                                </x-icon-button>

                                <div
                                    x-show="riwayatOpen" x-cloak
                                    class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4"
                                    @click.self="riwayatOpen = false"
                                    x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                    x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                >
                                    <div
                                        x-show="riwayatOpen"
                                        class="bg-white p-6 rounded-2xl max-w-lg w-full text-left"
                                        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                    >
                                        <h3 class="font-extrabold text-sm text-gray-800 mb-1">Riwayat Status &mdash; {{ $p->no_nota_dinas }}</h3>
                                        <p class="text-xs text-gray-400 mb-3.5">{{ $p->uker?->nama }}</p>

                                        @if ($p->statusLogs->isEmpty())
                                            <p class="text-sm text-gray-400 py-6 text-center">Belum ada riwayat perubahan.</p>
                                        @else
                                            <div class="max-h-96 overflow-y-auto space-y-2.5">
                                                @foreach ($p->statusLogs as $log)
                                                    <div class="border border-gray-100 rounded-lg p-3 text-sm">
                                                        <div class="flex items-center justify-between gap-2">
                                                            <p class="font-semibold text-gray-700">
                                                                @if ($log->status_lama)
                                                                    {{ $log->status_lama }} <span class="text-gray-400">&rarr;</span>
                                                                @else
                                                                    <span class="text-gray-400 italic">Diajukan</span> <span class="text-gray-400">&rarr;</span>
                                                                @endif
                                                                {{ $log->status_baru }}
                                                            </p>
                                                            <span class="text-[11px] text-gray-400 whitespace-nowrap">{{ $log->created_at?->format('d M Y H:i') }}</span>
                                                        </div>
                                                        <p class="text-gray-400 text-xs mt-1">{{ $log->changedBy?->name ?? '-' }}</p>
                                                        @if ($log->catatan_admin)
                                                            <p class="text-gray-600 text-xs mt-1.5">{{ $log->catatan_admin }}</p>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="pt-3">
                                            <x-button type="button" variant="secondary" @click="riwayatOpen = false">Tutup</x-button>
                                        </div>
                                    </div>
                                </div>

                                @if ($isAdmin)
                                    <x-icon-button type="button" variant="edit" label="Update Status" @click="open = true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    </x-icon-button>

                                    <div
                                        x-show="open" x-cloak
                                        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4"
                                        @click.self="open = false"
                                        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                        x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                    >
                                        <div
                                            x-show="open"
                                            class="bg-white p-6 rounded-2xl max-w-md w-full text-left"
                                            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                        >
                                            <h3 class="font-extrabold text-sm text-gray-800 mb-1">Update Status Permintaan</h3>
                                            <p class="text-xs text-gray-400 mb-3.5">{{ $p->no_nota_dinas }} &middot; {{ $p->uker?->nama }}</p>
                                            <form action="{{ route('permintaan-perangkat.updateStatus', $p) }}" method="POST" class="space-y-3">
                                                @csrf
                                                <div>
                                                    <x-input-label value="Status" />
                                                    <x-select name="status" class="mt-1.5 block w-full">
                                                        @foreach (\App\Models\PermintaanPerangkat::DAFTAR_STATUS as $s)
                                                            <option value="{{ $s }}" @selected($p->status === $s)>{{ $s }}</option>
                                                        @endforeach
                                                    </x-select>
                                                </div>
                                                <div>
                                                    <x-input-label value="Catatan (opsional)" />
                                                    <textarea name="catatan_admin" rows="3" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">{{ $p->catatan_admin }}</textarea>
                                                </div>
                                                <div class="flex gap-2 pt-1">
                                                    <x-button type="submit">Simpan</x-button>
                                                    <x-button type="button" variant="secondary" @click="open = false">Batal</x-button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isAdmin ? 9 : 8 }}" class="px-4 py-10 text-center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"></path></svg>
                                @if ($isAdmin && (request('uker_kode') || request('status')))
                                    <p class="text-gray-400 text-sm mb-3">Gak ada permintaan yang cocok dengan filter ini.</p>
                                    <x-button variant="secondary" size="sm" :href="route('permintaan-perangkat.index')">Reset Filter</x-button>
                                @elseif ($isAdmin)
                                    <p class="text-gray-400 text-sm">Belum ada permintaan perangkat dari cabang manapun.</p>
                                @else
                                    <p class="text-gray-400 text-sm mb-3">Belum ada permintaan perangkat.</p>
                                    <x-button type="button" size="sm" @click="ajukanOpen = true">Ajukan Permintaan</x-button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @unless ($isAdmin)
            <div
                x-show="ajukanOpen" x-cloak
                class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4"
                @click.self="ajukanOpen = false"
                x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            >
                <div
                    x-show="ajukanOpen"
                    class="bg-white p-6 rounded-2xl max-w-md w-full text-left"
                    x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                >
                    <h3 class="font-extrabold text-sm text-gray-800 mb-3.5">Ajukan Permintaan Perangkat</h3>
                    <form action="{{ route('permintaan-perangkat.store') }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <x-input-label value="No Nota Dinas" required />
                            <x-text-input type="text" name="no_nota_dinas" value="{{ old('no_nota_dinas') }}" class="mt-1.5 block w-full" required />
                            <x-input-error :messages="$errors->get('no_nota_dinas')" class="mt-1.5" />
                        </div>
                        <div>
                            <x-input-label value="Fungsi Requester" required />
                            <x-text-input type="text" name="fungsi_requester" value="{{ old('fungsi_requester') }}" class="mt-1.5 block w-full" placeholder="contoh: RSF, MRR" required />
                            <x-input-error :messages="$errors->get('fungsi_requester')" class="mt-1.5" />
                        </div>
                        <div>
                            <x-input-label value="Jumlah" required />
                            <x-text-input type="number" name="jumlah" value="{{ old('jumlah', 1) }}" min="1" class="mt-1.5 block w-full" required />
                            <x-input-error :messages="$errors->get('jumlah')" class="mt-1.5" />
                        </div>
                        <div>
                            <x-input-label value="Keterangan" required />
                            <textarea name="keterangan" rows="3" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" required>{{ old('keterangan') }}</textarea>
                            <x-input-error :messages="$errors->get('keterangan')" class="mt-1.5" />
                        </div>
                        <div class="flex gap-2 pt-1">
                            <x-button type="submit">Ajukan</x-button>
                            <x-button type="button" variant="secondary" @click="ajukanOpen = false">Batal</x-button>
                        </div>
                    </form>
                </div>
            </div>
        @endunless
    </div>
</x-app-layout>
