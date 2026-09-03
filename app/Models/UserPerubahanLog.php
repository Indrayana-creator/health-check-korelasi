<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPerubahanLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'field', 'nilai_lama', 'nilai_baru', 'changed_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // $timestamps=false karena tabel ini cuma punya created_at (log
    // insert-only, gak pernah diubah) -- sama pola kayak UkerPerubahanLog.
    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $log->created_at ??= now();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public const LABEL_FIELD = [
        'name' => 'Nama',
        'role' => 'Role',
        'uker_kode' => 'Uker',
        'is_active' => 'Status Aktif',
    ];
}
