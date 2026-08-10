<?php

namespace App\Notifications;

use App\Models\Uker;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// Dikirim ke user sebuah uker kalau mereka belum bikin form Health Check
// buat bulan kalender berjalan, dikirim mulai H-3 sebelum akhir bulan.
class ReminderPengisianHealthCheck extends Notification
{
    use Queueable;

    protected const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function __construct(protected Uker $uker)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $now = now();
        $namaBulan = self::NAMA_BULAN[(int) $now->month];
        $sisaHari = $now->daysInMonth - $now->day;

        return [
            'message' => "Pengingat: checklist Health Check {$this->uker->nama} bulan {$namaBulan} {$now->year} belum diisi -- {$sisaHari} hari lagi sebelum akhir bulan.",
            'url' => route('healthcheck.create'),
        ];
    }
}
