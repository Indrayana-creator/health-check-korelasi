<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthCheckItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'health_check_form_id', 'kategori', 'item_pemeriksaan', 'status', 'catatan',
        'status_tindak_lanjut', 'catatan_tindak_lanjut',
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
}
