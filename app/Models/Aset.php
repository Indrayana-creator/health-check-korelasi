<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aset extends Model
{
    use HasFactory, SoftDeletes;

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

    // Kategori Kode Aset yang "dipegang" oleh 1 orang -- field pemegang/PN/IP/
    // security wajib diisi kalau kategori-nya masuk daftar ini. Kategori lain
    // (UPS, Panel Listrik, Access Switch, dst) gak punya "pemegang", jadi field
    // itu opsional buat mereka.
    public const KATEGORI_PEMEGANG_INDIVIDU = [
        'PERSONAL COMPUTER', 'NOTEBOOK', 'TABLET', 'LAYAR MONITOR',
    ];

    public function uker()
    {
        return $this->belongsTo(Uker::class, 'uker_kode', 'kode');
    }

    public function kodeAset()
    {
        return $this->belongsTo(KodeAset::class, 'kode_aset_kode', 'kode');
    }

    public function editRequests()
    {
        return $this->hasMany(AsetEditRequest::class);
    }

    public function kondisiLogs()
    {
        // orderByDesc('id') sebagai tie-breaker -- beberapa perubahan bisa
        // kejadian dalam detik yang sama, created_at aja gak cukup buat
        // jamin urutan "paling baru di atas" tetap stabil & sesuai urutan
        // insert-nya (sama polanya kayak HealthCheckItem::statusLogs()).
        return $this->hasMany(AsetKondisiLog::class)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    // Admin selalu bisa edit tanpa perlu izin. User biasa cuma bisa edit
    // kalau ada permintaan yang sudah Disetujui dan belum pernah dipakai.
    public function bisaDiedit(User $user): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $this->editRequests()
            ->where('requested_by', $user->id)
            ->where('status', 'Disetujui')
            ->where('sudah_dipakai', false)
            ->exists();
    }

    public function permintaanEditMenunggu(): ?AsetEditRequest
    {
        return $this->editRequests()->where('status', 'Menunggu')->latest()->first();
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
