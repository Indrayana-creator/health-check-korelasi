<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// Command 1x jalan: bikin level "Area" (draft, dikelompokkan geografis) buat
// data RO 12 Surabaya, reassign 26 cabang ke area masing-masing, benerin 1
// anomali data (UNIT yang ke-detect jadi cabang sendiri), dan isi ulang
// kolom jenis buat semua uker berdasarkan nama.
class SetupAreaRo12 extends Command
{
    protected $signature = 'setup:area-ro12';

    protected $description = 'Bikin level Area (draft) buat data RO 12 Surabaya + isi ulang kolom jenis';

    // kode Area baru (mulai dari 8000 biar gak bentrok sama kode asli BRI)
    protected array $areaMap = [
        8001 => ['nama' => 'AREA MADURA', 'anggota' => [6, 61, 148, 95]],
        8002 => ['nama' => 'AREA SURABAYA', 'anggota' => [394, 583, 587, 1156, 584, 412, 96, 411, 360, 211, 172, 328]],
        8003 => ['nama' => 'AREA SIDOARJO', 'anggota' => [86, 553, 684]],
        8004 => ['nama' => 'AREA JATIM UTARA', 'anggota' => [11, 26, 23, 41, 55, 109]],
    ];

    public function handle()
    {
        $this->warn('Ini bikin pembagian Area DRAFT (tebakan geografis), bukan data resmi dari BRI. Bisa diubah lagi nanti.');

        // 1. Bikin 4 Area baru, nempel langsung ke Kanwil (146)
        foreach ($this->areaMap as $kode => $data) {
            DB::table('ukers')->updateOrInsert(
                ['kode' => $kode],
                [
                    'nama' => $data['nama'],
                    'jenis' => 'AREA',
                    'kode_spv' => 146,
                    'uker_spv' => 'Kantor Wilayah Surabaya',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $this->info("Area dibuat: {$data['nama']} (kode {$kode})");
        }

        // 2. Reassign tiap cabang (KC) ke Area yang sesuai
        $totalReassign = 0;
        foreach ($this->areaMap as $kodeArea => $data) {
            foreach ($data['anggota'] as $kodeCabang) {
                $cabang = DB::table('ukers')->where('kode', $kodeCabang)->first();
                if (! $cabang) {
                    $this->error("Cabang kode {$kodeCabang} tidak ditemukan, dilewati.");

                    continue;
                }
                DB::table('ukers')->where('kode', $kodeCabang)->update([
                    'kode_spv' => $kodeArea,
                    'uker_spv' => $data['nama'],
                    'updated_at' => now(),
                ]);
                $totalReassign++;
            }
        }
        $this->info("Total {$totalReassign} cabang berhasil dipindah ke Area masing-masing.");

        // 3. Benerin anomali: UNIT yang ke-detect jadi cabang sendiri
        //    (nama depannya "UNIT" tapi kode_spv = dirinya sendiri)
        $anomali = DB::table('ukers')
            ->whereColumn('kode', 'kode_spv')
            ->where('nama', 'like', 'UNIT%')
            ->get();

        foreach ($anomali as $u) {
            // Cari kata "BANGKALAN"/"SURABAYA"/dst di nama unit, cocokkan ke KC yang namanya mengandung kata itu
            $kcCocok = DB::table('ukers')
                ->where('nama', 'like', 'KC %')
                ->where(function ($q) use ($u) {
                    foreach (explode(' ', $u->nama) as $kata) {
                        if (strlen($kata) > 3) {
                            $q->orWhere('nama', 'like', "%{$kata}%");
                        }
                    }
                })
                ->first();

            if ($kcCocok) {
                DB::table('ukers')->where('kode', $u->kode)->update([
                    'kode_spv' => $kcCocok->kode,
                    'uker_spv' => $kcCocok->nama,
                    'updated_at' => now(),
                ]);
                $this->info("Anomali diperbaiki: {$u->nama} dipindah ke bawah {$kcCocok->nama}");
            }
        }

        // 4. Isi ulang kolom jenis berdasarkan nama (Kanwil/Area sudah diisi di atas)
        DB::table('ukers')->where('nama', 'like', 'KC %')->update(['jenis' => 'KC']);
        DB::table('ukers')->where('nama', 'like', 'KCP %')->update(['jenis' => 'KCP']);
        DB::table('ukers')->where('nama', 'like', 'UNIT%')->update(['jenis' => 'UNIT']);
        DB::table('ukers')->where('kode', 146)->update(['jenis' => 'KANWIL']);

        $this->info('Kolom jenis berhasil diisi ulang untuk semua uker berdasarkan pola nama.');
        $this->info('Selesai. Cek hasilnya di /ukers atau tabel ukers langsung.');
    }
}
