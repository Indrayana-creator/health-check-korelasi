<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'modul', 'aksi', 'jumlah_baris', 'keterangan'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper dipanggil dari controller lain setiap kali ada upload/delete massal
    public static function catat(string $modul, string $aksi, int $jumlahBaris, ?string $keterangan = null): self
    {
        return self::create([
            'user_id' => auth()->id(),
            'modul' => $modul,
            'aksi' => $aksi,
            'jumlah_baris' => $jumlahBaris,
            'keterangan' => $keterangan,
        ]);
    }
}
