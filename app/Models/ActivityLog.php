<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'modul', 'aksi', 'jumlah_baris', 'keterangan', 'detail_gagal'];

    protected $casts = [
        'detail_gagal' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper dipanggil dari controller lain setiap kali ada upload/delete massal.
    // $detailGagal (opsional) -- daftar baris yang gagal beserta alasannya,
    // biar bisa ditengok lagi kapan aja dari Log History, bukan cuma
    // ditampilin sekali doang lewat session flash lalu ilang.
    public static function catat(string $modul, string $aksi, int $jumlahBaris, ?string $keterangan = null, ?array $detailGagal = null): self
    {
        return self::create([
            'user_id' => auth()->id(),
            'modul' => $modul,
            'aksi' => $aksi,
            'jumlah_baris' => $jumlahBaris,
            'keterangan' => $keterangan,
            'detail_gagal' => $detailGagal ?: null,
        ]);
    }
}
