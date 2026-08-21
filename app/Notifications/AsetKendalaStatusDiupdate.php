<?php

namespace App\Notifications;

use App\Models\AsetKendala;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// Dua arah -- sama pola kayak MonitoringTindakLanjutDiupdate: admin update ->
// notif si pelapor; pelapor/cabang update -> notif semua admin.
class AsetKendalaStatusDiupdate extends Notification
{
    use Queueable;

    public function __construct(protected AsetKendala $kendala) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Status laporan kerusakan aset {$this->kendala->aset?->no_asset} diupdate jadi \"{$this->kendala->status}\"",
            'url' => route('monitoring.laporanAset.index'),
        ];
    }
}
