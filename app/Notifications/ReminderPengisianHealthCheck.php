<?php

namespace App\Notifications;

use App\Models\Uker;
use App\Support\PeriodeMingguan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// Dikirim ke user sebuah uker kalau mereka belum bikin form Health Check
// buat minggu kerja berjalan (Senin-Jumat), dikirim tiap hari Kamis (H-1
// sebelum deadline hari Jumat) -- lihat KirimReminderPengisianHealthCheck.
class ReminderPengisianHealthCheck extends Notification
{
    use Queueable;

    public function __construct(protected Uker $uker) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $now = now();
        [, $jumat] = PeriodeMingguan::rentang($now);
        $labelMinggu = PeriodeMingguan::label($now);

        return [
            'message' => "Pengingat: checklist Health Check {$this->uker->nama} minggu ini ({$labelMinggu}) belum diisi -- deadline Jumat, {$jumat->locale('id')->translatedFormat('d F Y')}.",
            'url' => route('healthcheck.create'),
        ];
    }
}
