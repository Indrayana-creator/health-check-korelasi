<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Rekap Health Check per Cabang
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            <p class="text-sm text-gray-500 mb-4">
                Data digabungkan (roll-up) dari seluruh uker/unit yang berada di bawah cabang yang sama,
                diurutkan dari compliance paling rendah agar cabang yang paling butuh perhatian terlihat lebih dulu.
            </p>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Cabang</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Uker Lapor</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Total Item</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">OK</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Not OK</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">N/A</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Belum</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Compliance</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($rekap as $r)
                            <tr>
                                <td class="px-4 py-2 font-medium text-gray-700">{{ $r['cabang'] }}</td>
                                <td class="px-4 py-2">{{ $r['jumlah_uker_lapor'] }}</td>
                                <td class="px-4 py-2">{{ $r['total_item'] }}</td>
                                <td class="px-4 py-2">{{ $r['ok'] }}</td>
                                <td class="px-4 py-2">{{ $r['not_ok'] }}</td>
                                <td class="px-4 py-2">{{ $r['na'] }}</td>
                                <td class="px-4 py-2">{{ $r['belum'] }}</td>
                                <td class="px-4 py-2 font-semibold">{{ $r['persen'] }}%</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded font-semibold
                                        {{ $r['status'] === 'SANGAT BAIK' ? 'bg-green-100 text-green-700' : ($r['status'] === 'BAIK' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                        {{ $r['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-4 text-center text-gray-500">Belum ada data health check.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
