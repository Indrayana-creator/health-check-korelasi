<?php

namespace App\Notifications;

use App\Models\HealthCheckForm;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// Dikirim ke semua user di uker terkait, waktu admin approve/reject form
// health check uker itu.
class HealthCheckApprovalDecided extends Notification
{
    use Queueable;

    public function __construct(protected HealthCheckForm $healthcheck)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $status = $this->healthcheck->status_approval;

        return [
            'message' => $status === 'Disetujui'
                ? "Form health check {$this->healthcheck->uker?->nama} periode {$this->healthcheck->periode} disetujui."
                : "Form health check {$this->healthcheck->uker?->nama} periode {$this->healthcheck->periode} ditolak, perlu direvisi.",
            'url' => route('healthcheck.edit', $this->healthcheck->id),
        ];
    }
}
