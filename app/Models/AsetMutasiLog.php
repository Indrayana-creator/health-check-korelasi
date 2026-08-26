<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsetMutasiLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'aset_id', 'uker_kode_lama', 'uker_kode_baru', 'changed_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // $timestamps=false karena tabel ini cuma punya created_at (log
    // insert-only, gak pernah diubah) -- sama pola kayak AsetKondisiLog &
    // PermintaanPerangkatStatusLog.
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

    public function ukerLama()
    {
        return $this->belongsTo(Uker::class, 'uker_kode_lama', 'kode');
    }

    public function ukerBaru()
    {
        return $this->belongsTo(Uker::class, 'uker_kode_baru', 'kode');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
