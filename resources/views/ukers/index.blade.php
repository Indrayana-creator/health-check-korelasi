<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Kelola Uker / Cabang</h2>
    </x-slot>

    <div class="p-7 space-y-4">

        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">{{ session('status') }}</div>
        @endif

        <div class="flex flex-wrap gap-2 items-end">
            <a href="{{ route('ukers.create') }}" class="bg-cakrawala text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-nusantara inline-flex items-center gap-1.5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M12 5v14M5 12h14"></path></svg>
                Tambah Uker/Cabang
            </a>
            <form method="GET" class="flex gap-2">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama/kode uker..." class="border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-900">Cari</button>
            </form>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Kode</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Nama</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Jenis</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Cabang Induk</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wide">Alamat</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($ukers as $u)
                        <tr>
                            <td class="px-4 py-2.5 font-mono text-xs text-gray-700">{{ $u->kode }}</td>
                            <td class="px-4 py-2.5 text-sm font-semibold text-gray-800">{{ $u->nama }}</td>
                            <td class="px-4 py-2.5">
                                <x-badge color="gray">{{ $u->jenis }}</x-badge>
                            </td>
                            <td class="px-4 py-2.5 text-sm text-gray-600">{{ $u->uker_spv }}</td>
                            <td class="px-4 py-2.5 text-sm text-gray-600">{{ Str::limit($u->alamat, 40) ?: '-' }}</td>
                            <td class="px-4 py-2.5 whitespace-nowrap text-right">
                                <a href="{{ route('ukers.edit', $u) }}" class="text-cakrawala text-sm font-semibold">Edit</a>
                                <form action="{{ route('ukers.destroy', $u) }}" method="POST" class="inline" onsubmit="return confirm('Hapus uker ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 text-sm font-semibold ml-2">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400 text-sm">Belum ada data uker.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $ukers->links() }}</div>
    </div>
</x-app-layout>
