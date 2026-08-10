<?php

namespace App\Notifications;

use App\Models\HealthCheckForm;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// Dikirim ke semua admin waktu user nyimpen checklist dan ada item yang BARU
// aja jadi "Not OK" (bukan yang udah Not OK dari sebelumnya, biar gak spam
// tiap kali form-nya disimpan ulang). Diarahkan ke Monitoring Kendala biar
// admin langsung liat semua item bermasalah, bukan cuma yang ini doang.
class HealthCheckItemFlaggedNotOk extends Notification
{
    use Queueable;

    public function __construct(protected HealthCheckForm $healthcheck, protected int $jumlahItemBaru) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $itemKata = $this->jumlahItemBaru === 1 ? 'item baru' : "{$this->jumlahItemBaru} item baru";

        return [
            'message' => "Ditemukan {$itemKata} bermasalah (Not OK) di form health check {$this->healthcheck->uker?->nama} periode {$this->healthcheck->periode}",
            'url' => route('monitoring.index'),
        ];
    }
}
