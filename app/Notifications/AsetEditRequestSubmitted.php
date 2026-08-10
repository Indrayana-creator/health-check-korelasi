<?php

namespace App\Notifications;

use App\Models\AsetEditRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// Dikirim ke semua admin waktu user ngajuin permintaan edit aset baru.
class AsetEditRequestSubmitted extends Notification
{
    use Queueable;

    public function __construct(protected AsetEditRequest $editRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "{$this->editRequest->requester?->name} mengajukan permintaan edit aset {$this->editRequest->aset?->no_asset}",
            'url' => route('aset.editRequests.index'),
        ];
    }
}
