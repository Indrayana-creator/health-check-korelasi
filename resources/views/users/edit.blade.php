<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit User</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    action="{{ route('users.update', $user) }}"
                    method="POST"
                    class="space-y-4"
                    x-data="{ role: '{{ old('role', $user->role) }}' }"
                >
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="mt-1 block w-full border-gray-300 rounded-md" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="mt-1 block w-full border-gray-300 rounded-md" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">PN (Personal Number, opsional)</label>
                        <input type="text" name="pn" value="{{ old('pn', $user->pn) }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        <p class="text-xs text-gray-400 mt-1">Kalau diisi, harus PN yang sudah terdaftar di data pekerja. Kosongkan dulu kalau data pekerja belum diimport.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah password" class="mt-1 block w-full border-gray-300 rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <select name="role" x-model="role" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option value="user" @selected(old('role', $user->role) == 'user')>User (per unit kerja)</option>
                            <option value="admin" @selected(old('role', $user->role) == 'admin')>Admin</option>
                        </select>
                    </div>

                    <div x-show="role === 'user'" x-cloak>
                        <label class="block text-sm font-medium text-gray-700">Uker</label>
                        <select name="uker_kode" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option value="">-- Pilih Uker --</option>
                            @foreach ($ukerList as $uker)
                                <option value="{{ $uker->kode }}" @selected(old('uker_kode', $user->uker_kode) == $uker->kode)>{{ $uker->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Simpan</button>
                        <a href="{{ route('users.index') }}" class="px-4 py-2 rounded border">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
