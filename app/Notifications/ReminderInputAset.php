<?php

namespace App\Notifications;

use App\Models\Uker;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// Dikirim manual oleh admin/atasan uker (tombol "Kirim Pengingat" di Dashboard,
// tab "Belum Ada Aset") ke user sebuah uker yang belum punya data aset sama
// sekali tercatat di sistem.
class ReminderInputAset extends Notification
{
    use Queueable;

    public function __construct(protected Uker $uker) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Pengingat: {$this->uker->nama} belum punya data aset yang tercatat di sistem, tolong segera diinput.",
            'url' => route('aset.create'),
        ];
    }
}
