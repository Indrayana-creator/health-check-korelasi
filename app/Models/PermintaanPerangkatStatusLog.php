<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermintaanPerangkatStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'permintaan_perangkat_id', 'status_lama', 'status_baru', 'catatan_admin', 'changed_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // $timestamps=false karena tabel ini cuma punya created_at (log
    // insert-only, gak pernah diubah) -- sama pola kayak AsetKondisiLog &
    // HealthCheckItemStatusLog.
    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $log->created_at ??= now();
        });
    }

    public function permintaanPerangkat()
    {
        return $this->belongsTo(PermintaanPerangkat::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
