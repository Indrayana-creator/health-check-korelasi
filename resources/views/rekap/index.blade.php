<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            Rekap Health Check per Cabang
        </h2>
    </x-slot>

    <div class="p-7 space-y-4">

        <p class="text-sm text-gray-500">
            Data digabungkan (roll-up) dari seluruh uker/unit yang berada di bawah cabang yang sama,
            diurutkan dari compliance paling rendah agar cabang yang paling butuh perhatian terlihat lebih dulu.
        </p>

        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Cabang</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Uker Lapor</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Total Item</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">OK</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Not OK</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">N/A</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Belum</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Compliance</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rekap as $r)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-semibold text-gray-800">{{ $r['cabang'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $r['jumlah_uker_lapor'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $r['total_item'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $r['ok'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $r['not_ok'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $r['na'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $r['belum'] }}</td>
                            <td class="px-4 py-3 text-sm font-bold text-gray-800">{{ $r['persen'] }}%</td>
                            <td class="px-4 py-3">
                                <x-badge :color="$r['status'] === 'SANGAT BAIK' ? 'green' : ($r['status'] === 'BAIK' ? 'yellow' : 'red')">
                                    {{ $r['status'] }}
                                </x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"></path></svg>
                                <p class="text-gray-400 text-sm">Belum ada data health check.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
