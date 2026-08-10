<?php

namespace App\Notifications;

use App\Models\AsetEditRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// Dikirim ke user yang ngajuin, waktu admin approve/reject permintaan editnya.
class AsetEditRequestDecided extends Notification
{
    use Queueable;

    public function __construct(protected AsetEditRequest $editRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $status = $this->editRequest->status;
        $noAsset = $this->editRequest->aset?->no_asset;

        return [
            'message' => $status === 'Disetujui'
                ? "Permintaan edit aset {$noAsset} disetujui, silakan edit sekarang."
                : "Permintaan edit aset {$noAsset} ditolak: {$this->editRequest->catatan_admin}",
            'url' => route('aset.edit', $this->editRequest->aset_id),
        ];
    }
}
