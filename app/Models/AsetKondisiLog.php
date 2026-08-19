<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsetKondisiLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'aset_id', 'kondisi_lama', 'kondisi_baru', 'changed_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // $timestamps=false karena tabel ini cuma punya created_at (log
    // insert-only, gak pernah diubah) -- sama polanya kayak
    // HealthCheckItemStatusLog.
    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $log->created_at ??= now();
        });
    }

    public function aset()
    {
        return $this->belongsTo(Aset::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
