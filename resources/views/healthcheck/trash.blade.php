<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Sampah Health Check</h2>
        <p class="text-xs text-gray-500 mt-0.5">{{ $formList->total() }} form yang sudah dihapus, bisa dipulihkan</p>
    </x-slot>

    <div class="p-7 space-y-4 max-w-[1360px]">

        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">{{ session('status') }}</div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500 max-w-3xl">
                Form health check yang dihapus ditaruh di sini dulu, gak langsung hilang permanen -- bisa dipulihkan kapan saja.
            </p>
            <x-button variant="secondary" :href="route('healthcheck.index')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
                Kembali ke Health Check
            </x-button>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Uker</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Periode</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Tanggal</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Dihapus Pada</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($formList as $form)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-semibold text-gray-700">{{ $form->uker?->nama }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $form->periode }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $form->tanggal_pemeriksaan }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $form->deleted_at?->format('d M Y, H:i') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <form action="{{ route('healthcheck.restore', $form->id) }}" method="POST" class="inline" onsubmit="return confirm('Pulihkan form ini?')">
                                    @csrf
                                    <x-icon-button variant="success" label="Pulihkan" type="submit">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M3 12a9 9 0 109-9 9.75 9.75 0 00-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
                                    </x-icon-button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 mx-auto mb-2 text-gray-300"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"></path></svg>
                                <p class="text-gray-400 text-sm">Sampah kosong -- gak ada form yang dihapus.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $formList->links() }}
        </div>
    </div>
</x-app-layout>
