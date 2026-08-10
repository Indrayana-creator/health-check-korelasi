<?php

namespace App\Notifications;

use App\Http\Controllers\MonitoringController;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// Dikirim ke admin waktu ada item checklist "Not OK" yang udah lewat ambang
// SLA (belum ditindaklanjuti > MonitoringController::AMBANG_HARI_MENDESAK
// hari) -- biar admin gak harus buka Monitoring Kendala duluan buat tau.
class KendalaSlaTerlambat extends Notification
{
    use Queueable;

    public function __construct(protected int $jumlahItem)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $itemKata = $this->jumlahItem === 1 ? '1 item' : "{$this->jumlahItem} item";
        $ambangHari = MonitoringController::AMBANG_HARI_MENDESAK;

        return [
            'message' => "{$itemKata} checklist \"Not OK\" sudah lewat {$ambangHari} hari belum ditindaklanjuti",
            'url' => route('monitoring.index'),
        ];
    }
}
