<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthCheckDokumentasiFoto extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'health_check_form_id', 'field', 'path', 'uploaded_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // $timestamps=false karena tabel ini cuma punya created_at (insert-only,
    // gak pernah diubah -- hapus foto = hapus barisnya, bukan update) --
    // sama pola kayak AsetKondisiLog/AsetMutasiLog.
    protected static function booted(): void
    {
        static::creating(function (self $foto) {
            $foto->created_at ??= now();
        });
    }

    public function form()
    {
        return $this->belongsTo(HealthCheckForm::class, 'health_check_form_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->path);
    }
}
