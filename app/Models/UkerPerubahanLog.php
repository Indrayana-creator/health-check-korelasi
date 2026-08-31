<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UkerPerubahanLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uker_kode', 'field', 'nilai_lama', 'nilai_baru', 'changed_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // $timestamps=false karena tabel ini cuma punya created_at (log
    // insert-only, gak pernah diubah) -- sama pola kayak AsetMutasiLog/
    // AsetKondisiLog/PermintaanPerangkatStatusLog.
    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $log->created_at ??= now();
        });
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // Label yang enak dibaca buat tiap nama kolom -- dipakai di view biar
    // gak nampilin nama kolom mentah ("kode_spv") ke user.
    public const LABEL_FIELD = [
        'nama' => 'Nama',
        'jenis' => 'Jenis',
        'alamat' => 'Alamat',
        'kode_spv' => 'Cabang Induk',
    ];
}
