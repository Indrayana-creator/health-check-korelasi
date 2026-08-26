<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    // asset(), bukan Storage::disk('public')->url() -- yang terakhir bikin
    // URL dari APP_URL statis di config (biasanya "localhost"), gak ngikutin
    // host request yang beneran dipakai buka app (mis. IP LAN pas diakses
    // dari HP). asset() otomatis ngikutin host request kalau ASSET_URL gak
    // di-set.
    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_path ? asset('storage/'.$this->foto_path) : null;
    }
}
