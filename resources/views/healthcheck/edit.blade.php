<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Isi Health Check &mdash; {{ $healthcheck->uker?->nama }} ({{ $healthcheck->periode }})
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
            @endif

            {{-- Status approval + tombol aksi workflow --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-1">Status Approval</h3>
                        <span class="px-2 py-1 text-xs rounded font-semibold
                            {{ match($healthcheck->status_approval) {
                                'Disetujui' => 'bg-green-100 text-green-700',
                                'Menunggu Approval' => 'bg-yellow-100 text-yellow-700',
                                'Ditolak' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-100 text-gray-600',
                            } }}">
                            {{ $healthcheck->status_approval }}
                        </span>
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
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">Submit untuk Approval</button>
                            </form>
                        @endif

                        @if ($healthcheck->status_approval === 'Menunggu Approval' && auth()->user()->role === 'admin')
                            <form action="{{ route('healthcheck.approve', $healthcheck) }}" method="POST" onsubmit="return confirm('Setujui form ini?')">
                                @csrf
                                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">Approve</button>
                            </form>
                            <button type="button" onclick="document.getElementById('modal-reject').classList.remove('hidden')" class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">Tolak</button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Modal kecil buat alasan tolak --}}
            @if ($healthcheck->status_approval === 'Menunggu Approval' && auth()->user()->role === 'admin')
                <div id="modal-reject" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                    <div class="bg-white p-6 rounded-lg max-w-md w-full">
                        <h3 class="font-semibold text-gray-800 mb-3">Alasan Penolakan</h3>
                        <form action="{{ route('healthcheck.reject', $healthcheck) }}" method="POST">
                            @csrf
                            <textarea name="catatan_approval" rows="3" class="w-full border-gray-300 rounded-md mb-3" placeholder="Jelaskan apa yang perlu direvisi..." required></textarea>
                            <div class="flex gap-2">
                                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">Tolak &amp; Kirim</button>
                                <button type="button" onclick="document.getElementById('modal-reject').classList.add('hidden')" class="px-4 py-2 rounded border text-sm">Batal</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <form action="{{ route('healthcheck.update', $healthcheck) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="bg-white p-6 shadow-sm sm:rounded-lg border-2 border-indigo-100">
                    <h3 class="font-semibold text-gray-800 mb-1">Status Tindak Lanjut</h3>
                    <p class="text-xs text-gray-400 mb-4">Diisi kalau ada item yang bermasalah (Not OK) dan perlu ditindaklanjuti/diperbaiki di lapangan. Field ini tetap bisa diisi meskipun form sudah disetujui.</p>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status_tindak_lanjut" class="mt-1 block w-full border-gray-300 rounded-md">
                            @foreach (\App\Models\HealthCheckForm::DAFTAR_STATUS_TINDAK_LANJUT as $s)
                                <option value="{{ $s }}" @selected($healthcheck->status_tindak_lanjut === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Catatan Tindak Lanjut (opsional)</label>
                        <textarea name="catatan_tindak_lanjut" rows="3" class="mt-1 block w-full border-gray-300 rounded-md" placeholder="contoh: sudah diajukan perbaikan AC ruang server ke vendor, estimasi selesai 3 hari">{{ $healthcheck->catatan_tindak_lanjut }}</textarea>
                    </div>
                </div>

                @if (!$healthcheck->itemsBisaDiedit())
                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm p-4 rounded-lg">
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
                    <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 class="font-semibold text-gray-800 mb-4">{{ $kategori }}</h3>

                        <div class="space-y-4">
                            @foreach ($items as $item)
                                <div class="border-b pb-3">
                                    <input type="hidden" name="items[{{ $globalIndex }}][id]" value="{{ $item->id }}">
                                    <p class="text-sm text-gray-700 mb-2">{{ $item->item_pemeriksaan }}</p>

                                    <div class="flex flex-wrap items-center gap-3">
                                        <select name="items[{{ $globalIndex }}][status]" class="border-gray-300 rounded-md text-sm {{ !$healthcheck->itemsBisaDiedit() ? 'pointer-events-none opacity-60 bg-gray-100' : '' }}" tabindex="{{ !$healthcheck->itemsBisaDiedit() ? '-1' : '0' }}">
                                            @foreach (['Belum Diperiksa', 'OK', 'Not OK', 'N/A'] as $opsi)
                                                <option value="{{ $opsi }}" @selected($item->status === $opsi)>{{ $opsi }}</option>
                                            @endforeach
                                        </select>

                                        <input
                                            type="text"
                                            name="items[{{ $globalIndex }}][catatan]"
                                            value="{{ $item->catatan }}"
                                            placeholder="Catatan (opsional)"
                                            class="flex-1 min-w-[200px] border-gray-300 rounded-md text-sm"
                                            {{ !$healthcheck->itemsBisaDiedit() ? 'readonly' : '' }}
                                        >
                                    </div>
                                </div>
                                @php $globalIndex++; @endphp
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="flex gap-2">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">Simpan</button>
                    <a href="{{ route('healthcheck.index') }}" class="px-4 py-2 rounded border">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
