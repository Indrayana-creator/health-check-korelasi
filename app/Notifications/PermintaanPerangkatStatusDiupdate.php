<?php

namespace App\Notifications;

use App\Models\PermintaanPerangkat;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// Dikirim ke user yang mengajukan, waktu admin update status permintaannya.
class PermintaanPerangkatStatusDiupdate extends Notification
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
            'message' => "Status permintaan perangkat (nota dinas {$this->permintaan->no_nota_dinas}) diupdate jadi {$this->permintaan->status}",
            'url' => route('permintaan-perangkat.index'),
        ];
    }
}
