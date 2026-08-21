<?php

namespace App\Notifications;

use App\Models\AsetKendala;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AsetKendalaDilaporkan extends Notification
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
            'message' => "Laporan kerusakan baru buat aset {$this->kendala->aset?->no_asset} ({$this->kendala->aset?->uker?->nama}) oleh {$this->kendala->reporter?->name}",
            'url' => route('monitoring.laporanAset.index'),
        ];
    }
}
