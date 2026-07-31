<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Tambah User</h2>
    </x-slot>

    <div class="p-7">
        <div class="max-w-xl mx-auto">
            <x-card padding="p-6">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    action="{{ route('users.store') }}"
                    method="POST"
                    class="space-y-4"
                    x-data="{ role: '{{ old('role', 'user') }}' }"
                >
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Nama</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">PN (Personal Number, opsional)</label>
                        <input type="text" name="pn" value="{{ old('pn') }}" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">
                        <p class="text-xs text-gray-400 mt-1.5">Kalau diisi, harus PN yang sudah terdaftar di data pekerja. Kosongkan dulu kalau data pekerja belum diimport.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Password</label>
                        <input type="password" name="password" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Role</label>
                        <select name="role" x-model="role" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">
                            <option value="user">User (per unit kerja)</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div x-show="role === 'user'" x-cloak>
                        <label class="block text-sm font-semibold text-gray-700">Uker</label>
                        <select name="uker_kode" class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala">
                            <option value="">-- Pilih Uker --</option>
                            @foreach ($ukerList as $uker)
                                <option value="{{ $uker->kode }}" @selected(old('uker_kode') == $uker->kode)>{{ $uker->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="bg-cakrawala text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-nusantara">Simpan</button>
                        <a href="{{ route('users.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50">Batal</a>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
