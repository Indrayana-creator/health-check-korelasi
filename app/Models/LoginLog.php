<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class LoginLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'pn_dicoba', 'status', 'ip_address', 'user_agent'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public const STATUS_BERHASIL = 'berhasil';

    public const STATUS_GAGAL_KREDENSIAL = 'gagal_kredensial';

    public const STATUS_GAGAL_NONAKTIF = 'gagal_nonaktif';

    public const STATUS_DITOLAK_SESI_LAIN = 'ditolak_sesi_lain';

    public const LABEL_STATUS = [
        self::STATUS_BERHASIL => 'Berhasil',
        self::STATUS_GAGAL_KREDENSIAL => 'PN/Password Salah',
        self::STATUS_GAGAL_NONAKTIF => 'Akun Nonaktif',
        self::STATUS_DITOLAK_SESI_LAIN => 'Ditolak (Sesi Lain Aktif)',
    ];

    // $timestamps=false karena tabel ini cuma punya created_at (log
    // insert-only, gak pernah diubah) -- sama polanya kayak log lain
    // (AsetKondisiLog, UkerPerubahanLog, dst).
    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $log->created_at ??= now();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Dipanggil dari LoginRequest & AuthenticatedSessionController tiap ada
    // percobaan login (berhasil ATAU gagal) -- satu pintu masuk biar format
    // pencatatannya konsisten (IP, user agent dipotong biar gak kepanjangan).
    public static function catat(?int $userId, ?string $pnDicoba, string $status, Request $request): self
    {
        return self::create([
            'user_id' => $userId,
            'pn_dicoba' => $pnDicoba,
            'status' => $status,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ? mb_substr($request->userAgent(), 0, 255) : null,
        ]);
    }
}
