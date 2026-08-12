<?php

namespace App\Notifications;

use App\Http\Controllers\MonitoringController;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// Dikirim ke user SATU cabang (bukan admin) waktu ada item checklist "Not OK"
// di uker sendiri + turunannya yang udah lewat ambang SLA -- biar petugas
// cabang proaktif tau tanpa harus buka Monitoring Kendala duluan. Beda dari
// KendalaSlaTerlambat yang dikirim ke SEMUA admin dengan total lintas cabang;
// ini per-cabang, jumlahnya cuma yang relevan buat subtree user itu sendiri.
class KendalaMendesakCabang extends Notification
{
    use Queueable;

    public function __construct(protected int $jumlahItem) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $itemKata = $this->jumlahItem === 1 ? '1 item' : "{$this->jumlahItem} item";
        $ambangHari = MonitoringController::AMBANG_HARI_MENDESAK;

        return [
            'message' => "{$itemKata} checklist \"Not OK\" di cabang Anda sudah lewat {$ambangHari} hari belum ditindaklanjuti",
            'url' => route('monitoring.index'),
        ];
    }
}
