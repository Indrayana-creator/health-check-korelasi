<?php

namespace App\Notifications;

use App\Models\Uker;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// Dikirim otomatis tiap hari Senin ke uker yang punya aset masuk kategori
// "Perlu Perhatian" (kondisi rusak/tidak layak, lewat umur pakai belum
// ditandai PH, atau belum dicek ulang 180 hari terakhir) -- lihat
// KirimReminderAsetPerluPerhatian.
class ReminderAsetPerluPerhatian extends Notification
{
    use Queueable;

    public function __construct(protected Uker $uker, protected int $jumlahAset) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Pengingat: {$this->uker->nama} punya {$this->jumlahAset} aset yang masuk kategori \"Perlu Perhatian\" (rusak/lewat umur/belum dicek ulang) -- tolong segera ditindaklanjuti.",
            'url' => route('aset.perluPerhatian'),
        ];
    }
}
