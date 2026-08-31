<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Monitoring Kendala</h2>
        <p class="text-xs text-gray-500 mt-0.5">{{ $laporanList->count() }} laporan kerusakan aset ditampilkan</p>
    </x-slot>

    <div class="p-7 space-y-4 max-w-[1360px]">

        <x-flash-status />

        <x-page-tabs :tabs="[
            ['label' => 'Checklist Not OK', 'href' => route('monitoring.index'), 'active' => false],
            ['label' => 'Laporan Manual Aset', 'href' => route('monitoring.laporanAset.index'), 'active' => true],
        ]" />

        <p class="text-sm text-gray-500 max-w-3xl">
            Laporan kerusakan yang dikirim langsung dari halaman Detail Aset (biasanya lewat scan QR di fisik
            perangkat) -- beda dari tab "Checklist Not OK" yang sumbernya item pemeriksaan Health Check terjadwal.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-red-100 text-red-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Total Laporan</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($totalKeseluruhan, 0, ',', '.') }}</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-gray-100 text-gray-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M12 8v4l3 3M12 22a10 10 0 100-20 10 10 0 000 20z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Belum Ditindaklanjuti</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($totalBelum, 0, ',', '.') }}</p>
            </x-card>
            <x-card padding="p-5">
                <div class="w-[38px] h-[38px] rounded-[10px] bg-green-100 text-green-600 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px]"><path d="M20 6L9 17l-5-5"></path></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-0.5">Selesai Diperbaiki</p>
                <p class="text-[28px] font-extrabold text-gray-800 tracking-tight">{{ number_format($totalSelesai, 0, ',', '.') }}</p>
            </x-card>
        </div>

        <x-card padding="p-3.5">
            <form method="GET" action="{{ route('monitoring.laporanAset.index') }}" class="flex flex-wrap gap-3 items-center">
                @if ($ukerFilterList->isNotEmpty())
                    <x-uker-filter-combobox
                        name="uker_kode"
                        :daftar-uker="$ukerFilterList->map(fn ($u) => ['kode' => $u->kode, 'nama' => $u->nama])->toJson()"
                        :selected="request('uker_kode')"
                        :initial-label="$ukerFilterList->firstWhere('kode', request('uker_kode'))?->nama"
                        class="min-w-[190px]"
                    />
                @endif
                <x-select name="status" class="min-w-[190px]">
                    <option value="">Semua Status</option>
                    @foreach (\App\Models\HealthCheckForm::DAFTAR_STATUS_TINDAK_LANJUT as $s)
                        <option value="{{ $s }}" @selected(request('status') == $s)>{{ $s }}</option>
                    @endforeach
                </x-select>
                <div class="flex gap-2">
                    <x-button type="submit">Terapkan</x-button>
                    @if (request('uker_kode') || request('status'))
                        <x-button variant="secondary" :href="route('monitoring.laporanAset.index')">Reset</x-button>
                    @endif
                </div>
            </form>
        </x-card>

        <x-table-scroll-hint />
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-cakrawala">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Foto</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Aset</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Deskripsi</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Pelapor</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Tanggal</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-white uppercase tracking-wide">Status</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($laporanList as $k)
                        @php
                            $aksenStatus = match ($k->status) {
                                'Selesai Diperbaiki' => 'border-l-[3px] border-l-green-400',
                                'Sedang Diproses' => 'border-l-[3px] border-l-amber-400',
                                default => 'border-l-[3px] border-l-red-400',
                            };
                        @endphp
                        <tr class="hover:bg-cakrawala/5 {{ $aksenStatus }}" x-data="{ open: false }">
                            <td class="px-4 py-3">
                                @if ($k->foto_url)
                                    <a href="{{ $k->foto_url }}" target="_blank" rel="noopener">
                                        <img src="{{ $k->foto_url }}" alt="Foto kerusakan" class="w-12 h-12 rounded-lg object-cover border border-gray-100">
                                    </a>
                                @else
                                    <span class="text-gray-300 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('aset.show', $k->aset_id) }}" class="font-semibold text-cakrawala hover:underline">{{ $k->aset?->no_asset }}</a>
                                <p class="text-gray-400 text-xs mt-0.5">{{ $k->aset?->uker?->nama }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 max-w-xs">{{ $k->deskripsi }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $k->reporter?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $k->created_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="match($k->status) { 'Selesai Diperbaiki' => 'green', 'Sedang Diproses' => 'yellow', default => 'gray' }">
                                    {{ $k->status }}
                                </x-badge>
                                @if ($k->catatan_admin)
                                    <p class="text-[11px] text-gray-400 mt-1 max-w-[180px]">{{ $k->catatan_admin }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
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
                                        <h3 class="font-extrabold text-sm text-gray-800 mb-1">Update Status Laporan</h3>
                                        <p class="text-xs text-gray-400 mb-3.5">{{ $k->aset?->no_asset }} &middot; {{ $k->aset?->uker?->nama }}</p>
                                        <form action="{{ route('monitoring.laporanAset.updateStatus', $k) }}" method="POST" class="space-y-3">
                                            @csrf
                                            <div>
                                                <x-input-label value="Status" />
                                                <x-select name="status" class="mt-1.5 block w-full">
                                                    @foreach (\App\Models\HealthCheckForm::DAFTAR_STATUS_TINDAK_LANJUT as $s)
                                                        <option value="{{ $s }}" @selected($k->status === $s)>{{ $s }}</option>
                                                    @endforeach
                                                </x-select>
                                            </div>
                                            <div>
                                                <x-input-label value="Catatan (opsional)" />
                                                <textarea name="catatan_admin" rows="3" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">{{ $k->catatan_admin }}</textarea>
                                            </div>
                                            <div class="flex gap-2 pt-1">
                                                <x-button type="submit">Simpan</x-button>
                                                <x-button type="button" variant="secondary" @click="open = false">Batal</x-button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M20 6L9 17l-5-5"></path></svg>
                                <p class="text-gray-400 text-sm">Belum ada laporan kerusakan aset.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
