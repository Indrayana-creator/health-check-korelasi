<?php

namespace App\Notifications;

use App\Models\AsetHapusRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// Dikirim ke user yang ngajuin, waktu admin approve/reject permintaan hapusnya.
class AsetHapusRequestDecided extends Notification
{
    use Queueable;

    public function __construct(protected AsetHapusRequest $hapusRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $status = $this->hapusRequest->status;
        $noAsset = $this->hapusRequest->aset?->no_asset;

        return [
            'message' => $status === 'Disetujui'
                ? "Permintaan hapus aset {$noAsset} disetujui, silakan hapus sekarang."
                : "Permintaan hapus aset {$noAsset} ditolak: {$this->hapusRequest->catatan_admin}",
            'url' => route('aset.show', $this->hapusRequest->aset_id),
        ];
    }
}
