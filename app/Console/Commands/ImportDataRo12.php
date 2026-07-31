<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\KodeAset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// Command khusus buat 1x import data historis RO 12 Surabaya (369 uker +
// 5.740 aset dari file DATA_ASET_TAGGING_NOTEBOOK_PC). Sengaja gak lewat
// AsetController::bulkUpload() karena data historis ini gak punya
// nama/jabatan/IP/security holder (cuma ada PN), yang di form biasa wajib
// diisi buat kategori Notebook/PC. Jalankan sekali aja, boleh dihapus
// setelah datanya masuk.
class ImportDataRo12 extends Command
{
    protected $signature = 'import:ro12-data';
    protected $description = 'Import data master uker + data aset historis RO 12 Surabaya';

    public function handle()
    {
        $this->importUker();
        $this->importAset();
    }

    protected function importUker()
    {
        $path = storage_path('app/seed_ukers_ro12.csv');
        if (!file_exists($path)) {
            $this->error("File tidak ditemukan: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle);
        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            [$kode, $nama, $kodeSpv, $ukerSpv] = $row;
            DB::table('ukers')->updateOrInsert(
                ['kode' => (int) $kode],
                [
                    'nama' => $nama,
                    'kode_spv' => (int) $kodeSpv,
                    'uker_spv' => $ukerSpv,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $count++;
        }
        fclose($handle);

        $this->info("Uker: {$count} baris berhasil diimport/diupdate.");
    }

    protected function importAset()
    {
        $path = storage_path('app/aset_ro12_ready_import.csv');
        if (!file_exists($path)) {
            $this->error("File tidak ditemukan: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle);

        $berhasil = 0;
        $dilewati = 0;
        $kodeAsetValid = KodeAset::pluck('kode')->flip();
        $ukerValid = DB::table('ukers')->pluck('kode')->flip();

        while (($row = fgetcsv($handle)) !== false) {
            [$ukerKode, $kodeAset, $merek, $tipeModel, $sn, $noAsset, $tahun, $kondisi, $pemegangPn, $keterangan] = $row;

            if (!isset($ukerValid[(int) $ukerKode]) || !isset($kodeAsetValid[$kodeAset])) {
                $dilewati++;
                continue;
            }

            DB::table('aset')->insert([
                'uker_kode' => (int) $ukerKode,
                'kode_aset_kode' => $kodeAset,
                'merek' => $merek,
                'tipe_model' => $tipeModel,
                'sn' => $sn,
                'no_asset' => $noAsset,
                'tahun_perolehan' => $tahun !== '' ? (int) $tahun : null,
                'kondisi' => $kondisi !== '' ? $kondisi : null,
                'pemegang_pn' => $pemegangPn !== '' ? $pemegangPn : null,
                'keterangan' => $keterangan !== '' ? $keterangan : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $berhasil++;
        }
        fclose($handle);

        $this->info("Aset: {$berhasil} baris berhasil diimport, {$dilewati} dilewati (uker/kode aset tidak valid).");

        ActivityLog::create([
            'user_id' => 1, // sesuaikan ID admin yang menjalankan, atau ambil dari akun admin pertama
            'modul' => 'aset',
            'aksi' => 'upload_massal',
            'jumlah_baris' => $berhasil,
            'keterangan' => 'Import data historis RO 12 Surabaya (DATA_ASET_TAGGING_NOTEBOOK_PC)',
        ]);
    }
}
