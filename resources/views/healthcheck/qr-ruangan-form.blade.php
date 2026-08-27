<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">Cetak QR Ruangan</h2>
    </x-slot>

    <div class="p-7">
        <div class="max-w-xl mx-auto">
            <x-card padding="p-6">
                <p class="mb-4 text-sm text-gray-500">
                    QR ini ditempel fisik di ruangan (mis. pintu ruang server, panel listrik) -- begitu di-scan,
                    langsung diarahkan ke form Health Check periode berjalan buat uker yang dipilih, terbuka di tab
                    kategori yang relevan. Kalau belum ada form buat periode ini, otomatis diarahkan buat bikin baru
                    dulu.
                </p>

                <form action="{{ route('healthcheck.qrRuanganCetak') }}" method="GET" class="space-y-4" x-data="{ ukerKodeTerpilih: '' }">
                    <div>
                        <x-uker-combobox
                            name="uker_kode"
                            label="Uker"
                            :daftar-uker="$ukerList->map(fn($u) => ['kode' => $u->kode, 'nama' => $u->nama])->toJson()"
                            model-value="ukerKodeTerpilih"
                            placeholder="Ketik nama atau kode uker..."
                        />
                    </div>

                    <div>
                        <x-input-label value="Kategori / Ruangan" required />
                        <x-select name="kategori" class="mt-1.5 block w-full" required>
                            <option value="">Pilih kategori...</option>
                            @foreach ($kategoriList as $k)
                                <option value="{{ $k }}">{{ $k }}</option>
                            @endforeach
                        </x-select>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <x-button type="submit" class="px-5 py-2.5">Generate QR</x-button>
                        <x-button variant="secondary" :href="route('healthcheck.index')" class="px-5 py-2.5">Kembali</x-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
