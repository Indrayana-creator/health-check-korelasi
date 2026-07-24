<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aset extends Model
{
    use HasFactory;

    protected $table = 'aset';

    protected $fillable = [
        'no_asset',
        'uker_kode',
        'kode_aset_kode',
        'merek',
        'tipe_model',
        'sn',
        'kapasitas_memori',
        'tahun_perolehan',
        'kondisi',
        'pemegang_nama',
        'jabatan',
        'pemegang_pn',
        'ip_address',
        'status_hardening',
        'status_bitlocker',
        'status_dlp',
        'status_antivirus',
        'keterangan',
    ];

    public const DAFTAR_KONDISI = [
        'NORMAL', 'NON DATABASE', 'PH/DISMANTEL', 'RUSAK', 'BACKUP',
        'SERVICE CENTER', 'TIDAK DIGUNAKAN', 'TIDAK LAYAK',
    ];

    public function uker()
    {
        return $this->belongsTo(Uker::class, 'uker_kode', 'kode');
    }

    public function kodeAset()
    {
        return $this->belongsTo(KodeAset::class, 'kode_aset_kode', 'kode');
    }

    public function getUmurTahunAttribute(): ?int
    {
        if (! $this->tahun_perolehan) {
            return null;
        }

        return now()->year - $this->tahun_perolehan;
    }

    public function getSudahPhAttribute(): bool
    {
        return $this->umur_tahun !== null && $this->umur_tahun >= 5;
    }

    // Bikin ASET ID otomatis format resmi: Z5-K-{kode_uker 4 digit}-{kode_aset}-{urutan 4 digit}
    public static function generateAsetId(int $ukerKode, string $kodeAsetKode): string
    {
        $ukerFormat = str_pad((string) $ukerKode, 4, '0', STR_PAD_LEFT);
        $urutanTerakhir = self::where('uker_kode', $ukerKode)
            ->where('kode_aset_kode', $kodeAsetKode)
            ->count();
        $urutanBaru = str_pad((string) ($urutanTerakhir + 1), 4, '0', STR_PAD_LEFT);

        return "Z5-K-{$ukerFormat}-{$kodeAsetKode}-{$urutanBaru}";
    }
}
