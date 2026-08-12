<?php

namespace App\Support;

// Skala kepatuhan resmi ESO Group BRI ("Enterprise IT Health Check") --
// satu-satunya sumber kebenaran buat nerjemahin PERSENTASE compliance
// (yang dihitung di tempat lain, misal HealthCheckForm::persenCompliance())
// jadi LABEL & warna badge. Jangan duplikat ambang batas 95/80 ini di
// tempat lain -- panggil class ini biar konsisten.
class ComplianceScale
{
    public const AMBANG_SANGAT_BAIK = 95;

    public const AMBANG_BAIK = 80;

    public static function label(float $persen): string
    {
        if ($persen >= self::AMBANG_SANGAT_BAIK) {
            return 'SANGAT BAIK';
        }

        if ($persen >= self::AMBANG_BAIK) {
            return 'BAIK';
        }

        return 'PERLU PERHATIAN';
    }

    // Dipakai buat <x-badge color="...">. Ini KEY semantik (green/yellow/red),
    // bukan class Tailwind literal -- view yang butuh class mentah (bukan lewat
    // x-badge) mapping sendiri dari key ini biar class-nya tetap literal di
    // dalam file blade (aman dari Tailwind JIT purge).
    public static function badgeColor(float $persen): string
    {
        return match (self::label($persen)) {
            'SANGAT BAIK' => 'green',
            'BAIK' => 'yellow',
            default => 'red',
        };
    }
}
