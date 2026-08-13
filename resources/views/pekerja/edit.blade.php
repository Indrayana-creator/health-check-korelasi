<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Edit Pekerja</h2>
    </x-slot>

    <div class="p-7">
        <div class="max-w-xl mx-auto">
            <x-card padding="p-6">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
                        Ada beberapa isian yang perlu diperbaiki, cek keterangan di bawah tiap field.
                    </div>
                @endif

                <form action="{{ route('pekerja.update', $pekerja) }}" method="POST" class="space-y-4" x-data="{ ukerKodeTerpilih: '{{ old('uker_kode', $pekerja->uker_kode) }}' }">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label value="PN (Personal Number)" required />
                        <x-text-input type="text" name="pn" value="{{ old('pn', $pekerja->pn) }}" readonly class="mt-1.5 block w-full bg-gray-50" required />
                        <p class="text-xs text-gray-400 mt-1.5">PN gak bisa diubah -- dipakai jadi referensi di akun User & form Health Check.</p>
                        <x-input-error :messages="$errors->get('pn')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="Nama Lengkap" required />
                        <x-text-input type="text" name="nama" value="{{ old('nama', $pekerja->nama) }}" class="mt-1.5 block w-full" required />
                        <x-input-error :messages="$errors->get('nama')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="Jabatan (opsional)" />
                        <x-text-input type="text" name="jabatan" value="{{ old('jabatan', $pekerja->jabatan) }}" class="mt-1.5 block w-full" />
                        <x-input-error :messages="$errors->get('jabatan')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-uker-combobox
                            name="uker_kode"
                            label="Uker"
                            :daftar-uker="$ukerList->map(fn ($u) => ['kode' => $u->kode, 'nama' => $u->nama])->toJson()"
                            model-value="ukerKodeTerpilih"
                            :initial-label="$ukerList->firstWhere('kode', old('uker_kode', $pekerja->uker_kode))?->nama"
                            placeholder="Ketik nama atau kode uker..."
                        />
                        <p class="text-xs text-gray-400 mt-1.5">Cuma level KC (Kantor Cabang) ke atas -- yang punya akun login cuma kantor cabang.</p>
                        <x-input-error :messages="$errors->get('uker_kode')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label value="No. HP (opsional)" />
                        <x-text-input type="text" name="no_hp" value="{{ old('no_hp', $pekerja->no_hp) }}" class="mt-1.5 block w-full" />
                        <x-input-error :messages="$errors->get('no_hp')" class="mt-1.5" />
                    </div>

                    <div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_petugas_it" value="1" class="rounded border-gray-300 text-cakrawala shadow-sm focus:ring-cakrawala" @checked(old('is_petugas_it', $pekerja->is_petugas_it))>
                            <span class="ms-2 text-sm text-gray-600">Petugas IT</span>
                        </label>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <x-button type="submit" class="px-5 py-2.5">Simpan</x-button>
                        <x-button variant="secondary" :href="route('pekerja.index')" class="px-5 py-2.5">Batal</x-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
