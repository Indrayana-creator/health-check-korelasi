<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthCheckItemStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'health_check_item_id', 'status', 'catatan', 'changed_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // $timestamps=false karena tabel ini cuma punya created_at (gak ada
    // updated_at -- log sifatnya insert-only, gak pernah diubah), jadi
    // created_at-nya diisi manual di sini biar konsisten lintas driver DB
    // (bukan cuma andalin DEFAULT CURRENT_TIMESTAMP dari migration).
    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $log->created_at ??= now();
        });
    }

    public function item()
    {
        return $this->belongsTo(HealthCheckItem::class, 'health_check_item_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
