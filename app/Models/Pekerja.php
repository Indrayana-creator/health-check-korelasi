<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pekerja extends Model
{
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
}
