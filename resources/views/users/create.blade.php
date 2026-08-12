<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Tambah User</h2>
    </x-slot>

    <div class="p-7">
        <div class="max-w-xl mx-auto">
            <x-card padding="p-6">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
                        Ada beberapa isian yang perlu diperbaiki, cek keterangan di bawah tiap field.
                    </div>
                @endif

                <form
                    action="{{ route('users.store') }}"
                    method="POST"
                    class="space-y-4"
                    x-data="{
                        role: '{{ old('role', 'user') }}',
                        ukerKodeTerpilih: '{{ old('uker_kode') }}',
                        statusLookupPn: '',
                        async cariUkerDariPn(pn) {
                            if (!pn) { this.statusLookupPn = ''; return; }
                            this.statusLookupPn = 'Mencari...';
                            try {
                                const res = await fetch(`/api/pekerja-uker/${pn}`);
                                if (!res.ok) { this.statusLookupPn = 'PN tidak ditemukan di data pekerja.'; return; }
                                const data = await res.json();
                                this.ukerKodeTerpilih = String(data.uker_kode);
                                this.statusLookupPn = `Ditemukan: ${data.nama} - ${data.uker_nama}`;
                            } catch (e) {
                                this.statusLookupPn = 'Gagal mencari PN.';
                            }
                        }
                    }"
                >
                    @csrf

                    <div>
                        <x-input-label value="Nama" required />
                        <x-text-input type="text" name="name" value="{{ old('name') }}" class="mt-1.5 block w-full" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="Email" required />
                        <x-text-input type="email" name="email" value="{{ old('email') }}" class="mt-1.5 block w-full" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="PN (Personal Number)" required />
                        <x-text-input type="text" name="pn" value="{{ old('pn') }}" @change="cariUkerDariPn($event.target.value)" class="mt-1.5 block w-full" required />
                        <p class="text-xs mt-1.5" :class="statusLookupPn.startsWith('Ditemukan') ? 'text-green-600' : 'text-gray-400'" x-text="statusLookupPn"></p>
                        <p class="text-xs text-gray-400 mt-1.5">Harus PN yang sudah terdaftar di data pekerja -- dipakai buat login. Isi PN dulu biar Uker di bawah otomatis terpilih (opsional, tetap bisa dipilih manual).</p>
                        <x-input-error :messages="$errors->get('pn')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="Password" required />
                        <x-text-input type="password" name="password" class="mt-1.5 block w-full" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="Role" />
                        <x-select name="role" x-model="role" class="mt-1.5 block w-full">
                            <option value="user">User (per unit kerja)</option>
                            <option value="admin">Admin</option>
                        </x-select>
                        <x-input-error :messages="$errors->get('role')" class="mt-1.5" />
                    </div>

                    <div x-show="role === 'user'" x-cloak>
                        <x-input-label value="Uker" />
                        <x-select name="uker_kode" x-model="ukerKodeTerpilih" class="mt-1.5 block w-full">
                            <option value="">-- Pilih Uker --</option>
                            @foreach ($ukerList as $uker)
                                <option value="{{ $uker->kode }}">{{ $uker->nama }}</option>
                            @endforeach
                        </x-select>
                        <p class="text-xs text-gray-400 mt-1.5">Cuma level KC (Kantor Cabang) ke atas -- yang punya akun login cuma kantor cabang. Otomatis terisi kalau PN di atas ketemu di data Pekerja.</p>
                        <x-input-error :messages="$errors->get('uker_kode')" class="mt-1.5" />
                    </div>

                    <div class="flex gap-2 pt-2">
                        <x-button type="submit" class="px-5 py-2.5">Simpan</x-button>
                        <x-button variant="secondary" :href="route('users.index')" class="px-5 py-2.5">Batal</x-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
