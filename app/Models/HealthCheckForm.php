<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthCheckForm extends Model
{
    use HasFactory;

    protected $fillable = ['uker_kode', 'pic_pn', 'tanggal_pemeriksaan', 'periode'];

    public function uker()
    {
        return $this->belongsTo(Uker::class, 'uker_kode', 'kode');
    }

    public function items()
    {
        return $this->hasMany(HealthCheckItem::class);
    }

    // Persentase compliance keseluruhan (OK / total langkah)
    public function persenCompliance(): float
    {
        $total = $this->items()->count();
        if ($total === 0) {
            return 0;
        }
        $ok = $this->items()->where('status', 'OK')->count();

        return round($ok / $total * 100, 1);
    }
}
