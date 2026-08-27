<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PermintaanPerangkat extends Model
{
    use HasFactory;

    public const DAFTAR_STATUS = [
        'Pending IT', 'Pending ESO', 'Pending LGA', 'Done Terkirim',
    ];

    // Ambang batas (hari) sebelum permintaan yang "nyangkut" di satu status
    // dianggap kelamaan -- sama nilainya kayak AMBANG_HARI_SLA_DIPROSES di
    // MonitoringController, biar konsisten satu aplikasi.
    public const AMBANG_HARI_LAMA = 7;

    protected $table = 'permintaan_perangkat';

    protected $fillable = [
        'kode_lacak', 'no_nota_dinas', 'tanggal_request', 'fungsi_requester', 'jumlah', 'keterangan',
        'status', 'catatan_admin', 'uker_kode', 'requested_by',
    ];

    protected $casts = [
        'tanggal_request' => 'date',
    ];

    protected $attributes = [
        'status' => 'Pending IT',
    ];

    // Kode lacak digenerate OTOMATIS begitu record dibuat -- gak pernah
    // diinput manual dari form manapun, biar formatnya selalu konsisten &
    // dijamin unik.
    protected static function booted(): void
    {
        static::creating(function (self $p) {
            $p->kode_lacak ??= self::generateKodeLacak();
        });
    }

    public static function generateKodeLacak(): string
    {
        do {
            $kode = 'PP-'.Str::upper(Str::random(8));
        } while (self::where('kode_lacak', $kode)->exists());

        return $kode;
    }

    public function uker()
    {
        return $this->belongsTo(Uker::class, 'uker_kode', 'kode');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function statusLogs()
    {
        // Tie-breaker id DESC sama kayak AsetKondisiLog/HealthCheckItem
        // statusLogs -- beberapa perubahan bisa kejadian dalam detik yang
        // sama, created_at aja gak cukup jamin urutan insert tetap stabil.
        return $this->hasMany(PermintaanPerangkatStatusLog::class)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    // Kapan status SEKARANG mulai berlaku -- dari log perubahan terbaru
    // kalau ada, fallback ke created_at permintaan itu sendiri kalau belum
    // pernah diupdate sama sekali (baru diajukan, masih status default).
    public function statusSejak(): Carbon
    {
        return $this->statusLogs->first()?->created_at ?? $this->created_at;
    }

    public function hariDiStatusIni(): int
    {
        return (int) floor($this->statusSejak()->diffInDays(now()));
    }

    // "Done Terkirim" gak pernah dianggap kelamaan -- itu status akhir,
    // gak ada lagi yang ditunggu.
    public function sudahLama(): bool
    {
        if ($this->status === 'Done Terkirim') {
            return false;
        }

        return $this->hariDiStatusIni() > self::AMBANG_HARI_LAMA;
    }
}
