<?php

namespace App\Support;

// Nerjemahin nama warna semantik (green/red/yellow/blue/gray -- yang sama
// dipakai buat <x-badge color="...">) jadi class Tailwind border kiri aksen
// baris tabel. SATU tempat ini doang -- sebelumnya tiap halaman (Aset,
// Health Check, Permintaan Perangkat, Monitoring Kendala) punya match()
// sendiri-sendiri buat badge DAN buat aksen baris, jadi gampang salah satu
// ke-update pas ada status baru, satu lagi ketinggalan (badge & border jadi
// gak sinkron). Class-nya harus ditulis literal (bukan dirakit dari string
// interpolation kayak "border-l-{$warna}-400") biar kescan sama Tailwind JIT
// -- lihat tailwind.config.js -> content, yang udah nyertain app/Support/*.php.
class StatusColor
{
    public static function aksenBorder(string $warna): string
    {
        return match ($warna) {
            'green' => 'border-l-[3px] border-l-green-400',
            'red' => 'border-l-[3px] border-l-red-400',
            'yellow' => 'border-l-[3px] border-l-amber-400',
            'blue' => 'border-l-[3px] border-l-blue-400',
            default => 'border-l-[3px] border-l-transparent',
        };
    }
}
