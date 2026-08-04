<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            Isi Health Check &mdash; {{ $healthcheck->uker?->nama }} ({{ $healthcheck->periode }})
        </h2>
    </x-slot>

    <div class="p-7">
        <div class="max-w-4xl mx-auto space-y-5">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">{{ session('status') }}</div>
            @endif

            {{-- Status approval + tombol aksi workflow --}}
            <div @if ($healthcheck->status_approval === 'Menunggu Approval' && auth()->user()->role === 'admin') x-data="{ openReject: false }" @endif>
            <x-card padding="p-6">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <h3 class="font-extrabold text-sm text-gray-800 mb-1.5">Status Approval</h3>
                        <x-badge :color="match($healthcheck->status_approval) { 'Disetujui' => 'green', 'Menunggu Approval' => 'yellow', 'Ditolak' => 'red', default => 'gray' }">
                            {{ $healthcheck->status_approval }}
                        </x-badge>
                        @if ($healthcheck->status_approval === 'Ditolak' && $healthcheck->catatan_approval)
                            <p class="text-sm text-red-600 mt-2">Alasan ditolak: {{ $healthcheck->catatan_approval }}</p>
                        @endif
                        @if ($healthcheck->status_approval === 'Disetujui')
                            <p class="text-xs text-gray-400 mt-2">Disetujui oleh PN {{ $healthcheck->approved_by_pn }} pada {{ $healthcheck->approved_at?->format('d M Y H:i') }}</p>
                        @endif
                    </div>

                    <div class="flex gap-2">
                        @if ($healthcheck->itemsBisaDiedit())
                            <form action="{{ route('healthcheck.submit', $healthcheck) }}" method="POST" onsubmit="return confirm('Submit form ini untuk approval? Item checklist tidak bisa diedit lagi sampai disetujui/ditolak.')">
                                @csrf
                                <x-button type="submit">Submit untuk Approval</x-button>
                            </form>
                        @endif

                        @if ($healthcheck->status_approval === 'Menunggu Approval' && auth()->user()->role === 'admin')
                            <form action="{{ route('healthcheck.approve', $healthcheck) }}" method="POST" onsubmit="return confirm('Setujui form ini?')">
                                @csrf
                                <x-button type="submit" variant="success">Approve</x-button>
                            </form>
                            <x-button type="button" variant="danger" @click="openReject = true">Tolak</x-button>
                        @endif
                    </div>
                </div>
            </x-card>

            {{-- Modal kecil buat alasan tolak --}}
            @if ($healthcheck->status_approval === 'Menunggu Approval' && auth()->user()->role === 'admin')
                <div x-show="openReject" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="openReject = false">
                    <div class="bg-white p-6 rounded-2xl max-w-md w-full">
                        <h3 class="font-extrabold text-sm text-gray-800 mb-3">Alasan Penolakan</h3>
                        <form action="{{ route('healthcheck.reject', $healthcheck) }}" method="POST">
                            @csrf
                            <textarea name="catatan_approval" rows="3" class="w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala mb-3" placeholder="Jelaskan apa yang perlu direvisi..." required></textarea>
                            <div class="flex gap-2">
                                <x-button type="submit" variant="danger">Tolak &amp; Kirim</x-button>
                                <x-button type="button" variant="secondary" @click="openReject = false">Batal</x-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
            </div>

            <form action="{{ route('healthcheck.update', $healthcheck) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <x-card padding="p-6">
                    <h3 class="font-extrabold text-sm text-gray-800 mb-1.5">Status Tindak Lanjut</h3>
                    <p class="text-xs text-gray-400 mb-4">Diisi kalau ada item yang bermasalah (Not OK) dan perlu ditindaklanjuti/diperbaiki di lapangan. Field ini tetap bisa diisi meskipun form sudah disetujui.</p>

                    <div class="mb-4">
                        <x-input-label value="Status" />
                        <x-select name="status_tindak_lanjut" class="mt-1.5 block w-full">
                            @foreach (\App\Models\HealthCheckForm::DAFTAR_STATUS_TINDAK_LANJUT as $s)
                                <option value="{{ $s }}" @selected($healthcheck->status_tindak_lanjut === $s)>{{ $s }}</option>
                            @endforeach
                        </x-select>
                    </div>

                    <div>
                        <x-input-label value="Catatan Tindak Lanjut (opsional)" />
                        <textarea name="catatan_tindak_lanjut" rows="3" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" placeholder="contoh: sudah diajukan perbaikan AC ruang server ke vendor, estimasi selesai 3 hari">{{ $healthcheck->catatan_tindak_lanjut }}</textarea>
                    </div>
                </x-card>

                @if (!$healthcheck->itemsBisaDiedit())
                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm p-4 rounded-xl">
                        @if ($healthcheck->sudahLewatTanggal())
                            Item checklist di bawah ini terkunci (read-only) karena sudah melewati tanggal pemeriksaan ({{ $healthcheck->tanggal_pemeriksaan->format('d M Y') }}). Pengisian harus dilakukan di hari yang sama, tidak bisa mengisi mundur.
                        @else
                            Item checklist di bawah ini terkunci (read-only) karena form sudah {{ strtolower($healthcheck->status_approval) }}.
                        @endif
                        Cuma Status Tindak Lanjut di atas yang masih bisa diubah.
                    </div>
                @endif

                @php $globalIndex = 0; @endphp

                @foreach ($itemsByKategori as $kategori => $items)
                    <x-card padding="p-6">
                        <h3 class="font-extrabold text-sm text-gray-800 mb-3.5">{{ $kategori }}</h3>

                        <div class="space-y-3.5">
                            @foreach ($items as $item)
                                <div class="border-b border-gray-100 pb-3">
                                    <input type="hidden" name="items[{{ $globalIndex }}][id]" value="{{ $item->id }}">
                                    <p class="text-sm text-gray-700 mb-2">{{ $item->item_pemeriksaan }}</p>

                                    <div class="flex flex-wrap items-center gap-3">
                                        <x-select name="items[{{ $globalIndex }}][status]" class="{{ !$healthcheck->itemsBisaDiedit() ? 'pointer-events-none opacity-60 bg-gray-100' : '' }}" tabindex="{{ !$healthcheck->itemsBisaDiedit() ? '-1' : '0' }}">
                                            @foreach (['Belum Diperiksa', 'OK', 'Not OK', 'N/A'] as $opsi)
                                                <option value="{{ $opsi }}" @selected($item->status === $opsi)>{{ $opsi }}</option>
                                            @endforeach
                                        </x-select>

                                        <x-text-input
                                            type="text"
                                            name="items[{{ $globalIndex }}][catatan]"
                                            value="{{ $item->catatan }}"
                                            placeholder="Catatan (opsional)"
                                            class="flex-1 min-w-[200px]"
                                            :readonly="!$healthcheck->itemsBisaDiedit()"
                                        />
                                    </div>
                                </div>
                                @php $globalIndex++; @endphp
                            @endforeach
                        </div>
                    </x-card>
                @endforeach

                <div class="flex gap-2">
                    <x-button type="submit" class="px-6 py-2.5">Simpan</x-button>
                    <x-button variant="secondary" :href="route('healthcheck.index')" class="px-5 py-2.5">Kembali</x-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
