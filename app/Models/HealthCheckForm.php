<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthCheckForm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uker_kode', 'pic_pn', 'tanggal_pemeriksaan', 'periode',
        'status_tindak_lanjut', 'catatan_tindak_lanjut',
        'status_approval', 'catatan_approval', 'approved_by_pn', 'approved_at',
    ];

    public const DAFTAR_STATUS_TINDAK_LANJUT = [
        'Belum Ditindaklanjuti', 'Sedang Diproses', 'Selesai Diperbaiki',
    ];

    public const DAFTAR_STATUS_APPROVAL = [
        'Draft', 'Menunggu Approval', 'Disetujui', 'Ditolak',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'tanggal_pemeriksaan' => 'date',
    ];

    // Item checklist cuma bisa diedit kalau masih Draft atau abis Ditolak (perlu revisi).
    // Status tindak lanjut/remediasi TIDAK ikut dikunci -- itu proses lain yang
    // tetap berjalan meskipun data health check-nya sudah disetujui.
    // Item checklist cuma bisa diedit kalau: (1) masih Draft/Ditolak, DAN
    // (2) hari ini masih sama dengan tanggal_pemeriksaan -- begitu tanggalnya
    // udah lewat, otomatis terkunci walau status approval-nya masih Draft.
    // Ini sesuai arahan Pak Indra: gak boleh isi mundur.
    public function itemsBisaDiedit(): bool
    {
        $statusOk = in_array($this->status_approval, ['Draft', 'Ditolak']);
        $tanggalOk = $this->tanggal_pemeriksaan?->isToday() ?? false;

        return $statusOk && $tanggalOk;
    }

    public function sudahLewatTanggal(): bool
    {
        return ! ($this->tanggal_pemeriksaan?->isToday() ?? false);
    }

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
