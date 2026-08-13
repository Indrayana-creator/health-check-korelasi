{{--
    Combobox pencarian Uker versi FILTER (dipakai di filter bar form GET,
    misal Monitoring Kendala/Permintaan Perangkat/Health Check/Data Aset) --
    reuse pola search-as-you-type yang sama kayak <x-uker-combobox> (form
    create/edit), tapi beda kebutuhan jadi komponen terpisah:
    - Self-contained (gak butuh x-data/modelValue dari parent, drop-in
      pengganti <x-select> yang sudah ada)
    - Ada opsi "Semua Uker" di paling atas buat clear filter (bukan wajib
      pilih 1 uker kayak form create)
    - Gak nampilin <label> terpisah, biar nyatu sama filter bar yang sudah
      ada (button "Terapkan" tetap yang submit, bukan auto-submit)

    Props:
    - name: nama field yang disubmit ke query string
    - daftarUker: JSON string [{kode, nama}] (pola sama kayak x-uker-combobox,
      caller kirim ->toJson())
    - selected: kode uker yang lagi aktif di filter (request('uker_kode'))
    - initialLabel: nama uker yang cocok sama $selected (caller yang cari,
      pola sama kayak x-uker-combobox)
    - placeholder: teks default + label opsi "clear"
--}}
@props(['name', 'daftarUker', 'selected' => null, 'initialLabel' => '', 'placeholder' => 'Semua Uker'])

<div
    x-data="{
        cariTeks: '{{ $initialLabel }}',
        nilaiTerpilih: '{{ $selected }}',
        bukaDaftar: false,
        daftar: {{ $daftarUker }},
        get hasilFilter() {
            if (!this.cariTeks) return this.daftar.slice(0, 30);
            const q = this.cariTeks.toLowerCase();
            return this.daftar.filter(u => u.nama.toLowerCase().includes(q) || String(u.kode).includes(q)).slice(0, 30);
        },
        pilih(u) {
            this.nilaiTerpilih = u ? u.kode : '';
            this.cariTeks = u ? u.nama : '';
            this.bukaDaftar = false;
        }
    }"
    class="relative"
>
    <input type="hidden" name="{{ $name }}" :value="nilaiTerpilih">
    <input
        type="text"
        x-model="cariTeks"
        @focus="bukaDaftar = true; cariTeks = ''"
        @click.away="bukaDaftar = false; if (!nilaiTerpilih) cariTeks = ''; else { const c = daftar.find(u => String(u.kode) === String(nilaiTerpilih)); if (c) cariTeks = c.nama; }"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        {{ $attributes->merge(['class' => 'block border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala']) }}
    >
    <div x-show="bukaDaftar" x-cloak class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-y-auto">
        <div @click="pilih(null)" class="px-3 py-2 text-sm hover:bg-cakrawala/10 cursor-pointer text-gray-500 border-b border-gray-100">
            {{ $placeholder }}
        </div>
        <template x-for="u in hasilFilter" :key="u.kode">
            <div
                @click="pilih(u)"
                class="px-3 py-2 text-sm hover:bg-cakrawala/10 cursor-pointer"
                x-text="u.kode + ' - ' + u.nama"
            ></div>
        </template>
        <div x-show="hasilFilter.length === 0" class="px-3 py-2 text-sm text-gray-400">Tidak ditemukan</div>
    </div>
</div>
