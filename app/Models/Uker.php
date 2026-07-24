<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Uker extends Model
{
    protected $fillable = ['kode', 'nama', 'jenis', 'kode_spv', 'uker_spv'];

    public function aset()
    {
        return $this->hasMany(Aset::class, 'uker_kode', 'kode');
    }
}
