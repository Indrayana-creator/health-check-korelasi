<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kelola Uker / Cabang</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
            @endif

            <div class="mb-4 flex flex-wrap gap-2 items-end">
                <a href="{{ route('ukers.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                    + Tambah Uker/Cabang
                </a>
                <form method="GET" class="flex gap-2">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama/kode uker..." class="border-gray-300 rounded-md text-sm">
                    <button type="submit" class="bg-gray-700 text-white px-4 py-2 rounded text-sm hover:bg-gray-800">Cari</button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Kode</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Nama</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Jenis</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Cabang Induk</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Alamat</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($ukers as $u)
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">{{ $u->kode }}</td>
                                <td class="px-4 py-2">{{ $u->nama }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-700">{{ $u->jenis }}</span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $u->uker_spv }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ Str::limit($u->alamat, 40) ?: '-' }}</td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <a href="{{ route('ukers.edit', $u) }}" class="text-indigo-600">Edit</a>
                                    <form action="{{ route('ukers.destroy', $u) }}" method="POST" class="inline" onsubmit="return confirm('Hapus uker ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 ml-2">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-4 text-center text-gray-500">Belum ada data uker.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $ukers->links() }}</div>
        </div>
    </div>
</x-app-layout>
