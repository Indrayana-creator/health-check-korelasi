<?php

namespace App\Support;

use Illuminate\Support\Carbon;

// Siklus Health Check sekarang mingguan (Senin-Jumat, hari kerja saja),
// bukan bulanan lagi. Class ini nentuin rentang tanggal minggu kerja yang
// berlaku buat tanggal tertentu -- dipakai buat nyaranin format periode
// yang gak ambigu di form "Buat Form Health Check", terutama pas minggu
// itu motong 2 bulan/tahun beda (misal "28 Juli - 1 Agustus 2026").
class PeriodeMingguan
{
    // Senin & Jumat dari minggu kerja yang berisi $tanggal.
    public static function rentang(Carbon $tanggal): array
    {
        $senin = $tanggal->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $jumat = $senin->copy()->addDays(4)->endOfDay();

        return [$senin, $jumat];
    }

    // Label periode format rentang tanggal, contoh: "10-14 Agustus 2026",
    // "28 Juli - 1 Agustus 2026" (beda bulan), atau "29 Desember 2026 - 2
    // Januari 2027" (beda tahun).
    public static function label(Carbon $tanggal): string
    {
        [$senin, $jumat] = self::rentang($tanggal);

        if ($senin->year !== $jumat->year) {
            return $senin->locale('id')->translatedFormat('d F Y').' - '.$jumat->locale('id')->translatedFormat('d F Y');
        }

        if ($senin->month !== $jumat->month) {
            return $senin->locale('id')->translatedFormat('d F').' - '.$jumat->locale('id')->translatedFormat('d F Y');
        }

        return $senin->format('d').'-'.$jumat->locale('id')->translatedFormat('d F Y');
    }
}
