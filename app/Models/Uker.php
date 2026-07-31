<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Uker extends Model
{
    protected $fillable = ['kode', 'nama', 'jenis', 'alamat', 'kode_spv', 'uker_spv'];

    // Biar route /ukers/{uker} pakai kode (yang emang dipakai di mana-mana
    // di aplikasi ini), bukan id internal yang gak pernah ditampilkan ke user
    public function getRouteKeyName(): string
    {
        return 'kode';
    }

    public function aset()
    {
        return $this->hasMany(Aset::class, 'uker_kode', 'kode');
    }

    public function pekerja()
    {
        return $this->hasMany(Pekerja::class, 'uker_kode', 'kode');
    }
}
