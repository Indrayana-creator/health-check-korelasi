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

    // Dulu kategori free text (bisa diketik apa aja, termasuk beda kapital
    // -- "Personal Computer" vs "PERSONAL COMPUTER" dianggap kategori BEDA
    // sama sistem). Ini bahaya karena Aset::KATEGORI_PEMEGANG_INDIVIDU (soal
    // wajib isi data pemegang & keamanan) nyocokin kategori PERSIS SAMA
    // teksnya -- typo dikit aja bikin aturan wajibnya gagal jalan tanpa
    // ketahuan. Dikunci ke daftar tetap yang emang udah dipakai di data
    // asli (RO12 Surabaya).
    public const DAFTAR_KATEGORI = [
        'PERSONAL COMPUTER', 'NOTEBOOK', 'TABLET', 'LAYAR MONITOR',
        'PRINTER & SCANNER', 'HARDISK', 'NAS', 'UPS', 'PANEL LISTRIK',
        'RACK NETWORK', 'ACCESS SWITCH', 'TITIK POWER', 'TITIK LAN', 'GENSET',
    ];

    public function aset()
    {
        return $this->hasMany(Aset::class, 'kode_aset_kode', 'kode');
    }

    public function getLabelAttribute(): string
    {
        return "{$this->kode} - {$this->nama}";
    }
}
