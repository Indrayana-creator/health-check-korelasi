<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pekerja extends Model
{
    use HasFactory;

    protected $table = 'pekerja';

    protected $primaryKey = 'pn';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['pn', 'nama', 'jabatan', 'status', 'uker_kode', 'is_petugas_it', 'no_hp'];

    public function uker()
    {
        return $this->belongsTo(Uker::class, 'uker_kode', 'kode');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'pn', 'pn');
    }

    // Link "wa.me" siap klik -- no_hp bisa kesimpen dalam format apa aja
    // (pakai strip, diawali 08, atau udah 62), jadi dinormalisasi di sini
    // tiap kali dipanggil, bukan cuma pas nyimpen.
    public function getWhatsappUrlAttribute(): ?string
    {
        if (! $this->no_hp) {
            return null;
        }

        $digit = preg_replace('/\D/', '', $this->no_hp);
        if (! $digit) {
            return null;
        }

        if (str_starts_with($digit, '0')) {
            $digit = '62'.substr($digit, 1);
        } elseif (! str_starts_with($digit, '62')) {
            $digit = '62'.$digit;
        }

        return "https://wa.me/{$digit}";
    }
}
