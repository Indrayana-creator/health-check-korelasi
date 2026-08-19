{{-- Petunjuk kecil khusus layar sempit (HP/tablet) -- tabel-tabel di app ini
     rata-rata terlalu lebar buat muat penuh di layar kecil, jadi wrapper-nya
     overflow-x-auto (bisa di-scroll ke samping). Tanpa petunjuk ini, kolom
     yang kepotong di kanan gak kelihatan ada isinya sama sekali, gak ada
     tanda kalau tabelnya sebenarnya bisa digeser. --}}
<p class="sm:hidden flex items-center gap-1.5 text-[11px] text-gray-400 mb-1.5 px-0.5">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 flex-none"><path d="M9 18l6-6-6-6"></path></svg>
    Geser tabel ke samping untuk lihat kolom lainnya
</p>
