<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Isi Health Check &mdash; {{ $healthcheck->uker?->nama }} ({{ $healthcheck->periode }})
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
            @endif

            <form action="{{ route('healthcheck.update', $healthcheck) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

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
                                        <select name="items[{{ $globalIndex }}][status]" class="border-gray-300 rounded-md text-sm">
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
                                        >
                                    </div>
                                </div>
                                @php $globalIndex++; @endphp
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="flex gap-2">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">Simpan Hasil Pemeriksaan</button>
                    <a href="{{ route('healthcheck.index') }}" class="px-4 py-2 rounded border">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
