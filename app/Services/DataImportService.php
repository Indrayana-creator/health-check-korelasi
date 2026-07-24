<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DataImportService
{
    /**
     * Import dari Database_All_Pekerja_BRI_SBY.xlsx (sheet "DATABASE").
     * Sekaligus ngisi tabel `ukers` (dari kolom KODE BRANCH / NAMA UKER / dst)
     * dan tabel `pekerja`.
     */
    public function importPekerjaFile(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('DATABASE');

        if (! $sheet) {
            throw new \Exception('Sheet "DATABASE" tidak ditemukan di file ini.');
        }

        $highestRow = $sheet->getHighestRow();
        $ukerCount = 0;
        $pekerjaCount = 0;
        $seenUkerCodes = [];

        // Kolom sesuai struktur asli: A=NO, B=PN, D=Nama Pekerja, E=Jabatan,
        // F=Status, H=KODE BRANCH, I=NAMA UKER, J=Kode Supervisi, K=Supervisi
        for ($row = 2; $row <= $highestRow; $row++) {
            $pn = trim((string) $sheet->getCell("B{$row}")->getValue());
            $namaPekerja = trim((string) $sheet->getCell("D{$row}")->getValue());
            $jabatan = trim((string) $sheet->getCell("E{$row}")->getValue());
            $status = trim((string) $sheet->getCell("F{$row}")->getValue());
            $kodeBranch = $sheet->getCell("H{$row}")->getValue();
            $namaUker = trim((string) $sheet->getCell("I{$row}")->getValue());
            $kodeSpv = $sheet->getCell("J{$row}")->getValue();
            $ukerSpv = trim((string) $sheet->getCell("K{$row}")->getValue());

            if ($pn === '' || $kodeBranch === null) {
                continue; // baris kosong/rusak, skip
            }

            $kodeBranch = (int) $kodeBranch;
            $kodeSpv = $kodeSpv !== null ? (int) $kodeSpv : 0;

            // isi/update tabel ukers (cukup sekali per kode, tapi updateOrInsert aman diulang)
            if (! isset($seenUkerCodes[$kodeBranch])) {
                DB::table('ukers')->updateOrInsert(
                    ['kode' => $kodeBranch],
                    [
                        'nama' => $namaUker,
                        'kode_spv' => $kodeSpv,
                        'uker_spv' => $ukerSpv,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                $seenUkerCodes[$kodeBranch] = true;
                $ukerCount++;
            }

            // isi/update tabel pekerja
            $ukerAda = DB::table('ukers')->where('kode', $kodeBranch)->exists();
            DB::table('pekerja')->updateOrInsert(
                ['pn' => $pn],
                [
                    'nama' => $namaPekerja,
                    'jabatan' => $jabatan,
                    'status' => $status,
                    'uker_kode' => $ukerAda ? $kodeBranch : null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $pekerjaCount++;
        }

        return [
            'uker_diproses' => $ukerCount,
            'pekerja_diproses' => $pekerjaCount,
        ];
    }

    /**
     * Import dari Daftar_Petugas_IT_RO_Surabaya.xlsx (2 sheet: "Kanwil" dan
     * "Data Petugas IT RO Surabaya "). Nandain kolom is_petugas_it = true
     * di tabel pekerja berdasarkan PN yang cocok. Pekerja HARUS sudah ada
     * duluan (jalankan importPekerjaFile dulu sebelum ini).
     */
    public function importPetugasItFile(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $ditandai = 0;
        $tidakKetemu = 0;

        // [nama sheet persis, kolom PN, kolom No HP, baris data mulai dari]
        // Perhatikan nama sheet kedua ada spasi di akhir ("...Surabaya ")
        $sheetConfigs = [
            ['Kanwil', 'B', 'E', 3],
            ['Data Petugas IT RO Surabaya ', 'D', 'E', 3],
        ];

        foreach ($sheetConfigs as [$sheetName, $colPn, $colHp, $startRow]) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (! $sheet) {
                continue; // skip diam-diam kalau nama sheet beda, jangan gagalin semua proses
            }

            $highestRow = $sheet->getHighestRow();
            for ($row = $startRow; $row <= $highestRow; $row++) {
                $pn = trim((string) $sheet->getCell("{$colPn}{$row}")->getValue());
                $noHp = trim((string) $sheet->getCell("{$colHp}{$row}")->getValue());

                if ($pn === '') {
                    continue;
                }

                $updated = DB::table('pekerja')
                    ->where('pn', $pn)
                    ->update([
                        'is_petugas_it' => true,
                        'no_hp' => $noHp !== '' ? $noHp : null,
                        'updated_at' => now(),
                    ]);

                if ($updated) {
                    $ditandai++;
                } else {
                    $tidakKetemu++;
                }
            }
        }

        return [
            'ditandai_petugas_it' => $ditandai,
            'pn_tidak_ketemu' => $tidakKetemu,
        ];
    }
}
