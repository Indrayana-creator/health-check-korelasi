<?php

namespace App\Notifications;

use App\Models\AsetHapusRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// Dikirim ke semua admin waktu user ngajuin permintaan hapus aset baru.
class AsetHapusRequestSubmitted extends Notification
{
    use Queueable;

    public function __construct(protected AsetHapusRequest $hapusRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "{$this->hapusRequest->requester?->name} mengajukan permintaan hapus aset {$this->hapusRequest->aset?->no_asset}",
            'url' => route('aset.hapusRequests.index'),
        ];
    }
}
