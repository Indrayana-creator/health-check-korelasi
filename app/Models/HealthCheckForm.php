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
        'foto_ruang_server_url', 'foto_storage_cctv_url', 'foto_panel_ups_url',
    ];

    public const DAFTAR_STATUS_TINDAK_LANJUT = [
        'Belum Ditindaklanjuti', 'Sedang Diproses', 'Selesai Diperbaiki',
    ];

    public const DAFTAR_STATUS_APPROVAL = [
        'Draft', 'Menunggu Approval', 'Disetujui', 'Ditolak',
    ];

    // Kategori E "Dokumentasi Visual" (form resmi ESO Group BRI) -- bukan
    // checklist OK/Not OK/N/A, cuma link foto bukti, jadi TIDAK ikut
    // persenCompliance() di bawah. Metadata (instruksi & kondisi ideal)
    // disimpan di satu tempat ini biar dipakai bareng sama view.
    public const FIELD_DOKUMENTASI_VISUAL = [
        'foto_ruang_server_url' => [
            'label' => 'Foto Ruang Server/Jaringan',
            'instruksi' => 'Ambil foto area ruang server/jaringan secara keseluruhan dari depan pintu masuk',
            'kondisi_ideal' => 'ruangan, AC, rak, dan kondisi kerapian terlihat jelas',
        ],
        'foto_storage_cctv_url' => [
            'label' => 'Foto Status Storage & Channel CCTV',
            'instruksi' => 'Screenshot atau foto layar NVR yang menampilkan status storage dan seluruh channel',
            'kondisi_ideal' => 'seluruh channel aktif dan informasi kapasitas storage terbaca jelas',
        ],
        'foto_panel_ups_url' => [
            'label' => 'Foto Panel LCD UPS',
            'instruksi' => 'Ambil foto panel LCD UPS yang menampilkan informasi beban, baterai, dan tegangan',
            'kondisi_ideal' => 'nilai Load, status baterai, dan tegangan terbaca dengan jelas',
        ],
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

    // Persentase compliance keseluruhan (OK / total langkah) -- murni dari
    // item checklist (kategori dinamis dari config), Kategori E (dokumentasi
    // visual) gak pernah ikut dihitung di sini.
    public function persenCompliance(): float
    {
        $total = $this->items()->count();
        if ($total === 0) {
            return 0;
        }
        $ok = $this->items()->where('status', 'OK')->count();

        return round($ok / $total * 100, 1);
    }

    // Jumlah foto dokumentasi visual (Kategori E) yang sudah diisi, dari 0-3.
    public function jumlahFotoDokumentasiTerisi(): int
    {
        return collect(array_keys(self::FIELD_DOKUMENTASI_VISUAL))
            ->filter(fn ($field) => filled($this->{$field}))
            ->count();
    }

    // Revisi Pak Indra -- Dokumentasi Visual sebelumnya cuma nerima link URL
    // (user paste link foto dari luar), sekarang harus foto/upload LANGSUNG
    // dari app. Kolom foto_..._url tetap dipakai (gak di-rename, hindari
    // migration mubazir) tapi isinya sekarang PATH di storage lokal, bukan
    // URL asli -- makanya butuh accessor biar ->foto_ruang_server_url dkk
    // tetap ngasih URL yang bisa langsung ditampilin (kayak pola foto_url di
    // AsetKendala/HealthCheckItem), transparan buat semua kode lain yang
    // udah baca field ini (blade, jumlahFotoDokumentasiTerisi(), dst).
    public function getFotoRuangServerUrlAttribute(): ?string
    {
        return $this->dokumentasiVisualUrl('foto_ruang_server_url');
    }

    public function getFotoStorageCctvUrlAttribute(): ?string
    {
        return $this->dokumentasiVisualUrl('foto_storage_cctv_url');
    }

    public function getFotoPanelUpsUrlAttribute(): ?string
    {
        return $this->dokumentasiVisualUrl('foto_panel_ups_url');
    }

    protected function dokumentasiVisualUrl(string $field): ?string
    {
        $path = $this->getRawOriginal($field);

        return $path ? asset('storage/'.$path) : null;
    }
}
