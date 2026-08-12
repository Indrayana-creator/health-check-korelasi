<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthCheckItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'health_check_form_id', 'kategori', 'item_pemeriksaan', 'status', 'catatan',
        'status_tindak_lanjut', 'catatan_tindak_lanjut', 'mulai_diproses_at',
    ];

    protected $casts = [
        'mulai_diproses_at' => 'datetime',
    ];

    // Default in-memory biar konsisten sama default kolom DB-nya
    protected $attributes = [
        'status' => 'Belum Diperiksa',
        'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
    ];

    public function form()
    {
        return $this->belongsTo(HealthCheckForm::class, 'health_check_form_id');
    }

    public function statusLogs()
    {
        // orderByDesc('id') sebagai tie-breaker -- beberapa update bisa
        // kejadian dalam detik yang sama, created_at aja gak cukup buat
        // jamin urutan "paling baru di atas" tetap stabil & sesuai urutan
        // insert-nya.
        return $this->hasMany(HealthCheckItemStatusLog::class)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
