<?php

namespace App\Notifications;

use App\Models\HealthCheckForm;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// Dikirim ke semua admin waktu form health check disubmit buat approval.
class HealthCheckSubmittedForApproval extends Notification
{
    use Queueable;

    public function __construct(protected HealthCheckForm $healthcheck) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Form health check {$this->healthcheck->uker?->nama} periode {$this->healthcheck->periode} menunggu approval",
            'url' => route('healthcheck.edit', $this->healthcheck->id),
        ];
    }
}
