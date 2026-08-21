<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

// Laporan kerusakan yang dikirim LANGSUNG dari halaman Detail Aset (biasanya
// abis scan QR di fisik perangkat), lengkap sama foto -- beda dari Monitoring
// Kendala yang sumbernya item checklist Health Check "Not OK".
class AsetKendala extends Model
{
    protected $table = 'aset_kendala';

    protected $fillable = [
        'aset_id', 'deskripsi', 'foto_path', 'status', 'catatan_admin', 'reported_by',
    ];

    protected $attributes = [
        'status' => 'Belum Ditindaklanjuti',
    ];

    public function aset()
    {
        return $this->belongsTo(Aset::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_path ? Storage::disk('public')->url($this->foto_path) : null;
    }
}
