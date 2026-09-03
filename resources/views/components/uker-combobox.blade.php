{{--
    Combobox pencarian Uker, reusable.
    Props:
    - name: nama field input hidden yang disubmit
    - daftarUker: array [{kode, nama}] dari server
    - modelValue: nama variabel Alpine (di parent) buat value terpilih (kode uker)
    - label: label yang ditampilkan
    - initialLabel: teks awal yang ditampilkan (buat form Edit, isi nama uker yang sudah terpilih)

    Otomatis update teks tampilan kalau modelValue berubah dari luar (misal hasil
    auto-fill dari lookup PN), lewat $watch di x-init.
--}}
@props(['name', 'label' => 'Uker', 'daftarUker', 'modelValue', 'placeholder' => '-- Cari & pilih uker --', 'initialLabel' => '', 'required' => false])

<div
    x-data="{
        cariTeks: '{{ $initialLabel }}',
        bukaDaftar: false,
        daftar: {{ $daftarUker }},
        get hasilFilter() {
            if (!this.cariTeks) return this.daftar.slice(0, 30);
            const q = this.cariTeks.toLowerCase();
            return this.daftar.filter(u => u.nama.toLowerCase().includes(q) || String(u.kode).includes(q)).slice(0, 30);
        }
    }"
    x-init="$watch(() => {{ $modelValue }}, (nilaiBaru) => {
        const cocok = daftar.find(u => String(u.kode) === String(nilaiBaru));
        if (cocok) cariTeks = cocok.nama;
    })"
    class="relative"
>
    <label class="block text-sm font-semibold text-gray-700">{{ $label }} @if ($required)<span class="text-red-500">*</span>@endif</label>
    <input type="hidden" name="{{ $name }}" :value="{{ $modelValue }}">
    <input
        type="text"
        x-model="cariTeks"
        @focus="bukaDaftar = true"
        @click.away="bukaDaftar = false"
        placeholder="{{ $placeholder }}"
        class="mt-1.5 block w-full border-gray-300 rounded-lg text-sm focus:border-cakrawala focus:ring-cakrawala"
    >
    <div x-show="bukaDaftar" x-cloak class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-y-auto">
        <template x-for="u in hasilFilter" :key="u.kode">
            <div
                @click="{{ $modelValue }} = u.kode; cariTeks = u.nama; bukaDaftar = false"
                class="px-3 py-2 text-sm hover:bg-cakrawala/10 cursor-pointer"
                x-text="u.kode + ' - ' + u.nama"
            ></div>
        </template>
        <div x-show="hasilFilter.length === 0" class="px-3 py-2 text-sm text-gray-400">Tidak ditemukan</div>
    </div>
</div>