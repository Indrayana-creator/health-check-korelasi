<?php

namespace App\Notifications;

use App\Models\PermintaanPerangkat;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// Dikirim ke semua admin waktu cabang mengajukan permintaan perangkat baru.
class PermintaanPerangkatDiajukan extends Notification
{
    use Queueable;

    public function __construct(protected PermintaanPerangkat $permintaan) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "{$this->permintaan->uker?->nama} mengajukan permintaan perangkat (nota dinas {$this->permintaan->no_nota_dinas})",
            'url' => route('permintaan-perangkat.index'),
        ];
    }
}
