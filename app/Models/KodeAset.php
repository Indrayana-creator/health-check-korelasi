<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KodeAset extends Model
{
    use HasFactory;

    protected $table = 'kode_aset';

    protected $primaryKey = 'kode';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['kode', 'kategori', 'nama'];

    public function aset()
    {
        return $this->hasMany(Aset::class, 'kode_aset_kode', 'kode');
    }

    public function getLabelAttribute(): string
    {
        return "{$this->kode} - {$this->nama}";
    }
}
