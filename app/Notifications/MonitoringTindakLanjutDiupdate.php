<?php

namespace App\Notifications;

use App\Models\HealthCheckItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// Dua arah -- kalau admin yang update, dikirim ke user cabang terkait;
// kalau user cabang yang update, dikirim ke semua admin. Sama pola kayak
// PermintaanPerangkatDiajukan/Decided & AsetEditRequestSubmitted/Decided
// (aksi 1 pihak -> notif pihak lain), sebelumnya belum ada notifikasi
// sama sekali di alur Monitoring Kendala.
class MonitoringTindakLanjutDiupdate extends Notification
{
    use Queueable;

    public function __construct(protected HealthCheckItem $item) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Status tindak lanjut item '{$this->item->item_pemeriksaan}' ({$this->item->form?->uker?->nama}) diupdate jadi \"{$this->item->status_tindak_lanjut}\"",
            'url' => route('monitoring.index'),
        ];
    }
}
