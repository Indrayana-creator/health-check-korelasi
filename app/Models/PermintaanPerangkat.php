<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanPerangkat extends Model
{
    use HasFactory;

    public const DAFTAR_STATUS = [
        'Pending IT', 'Pending ESO', 'Pending LGA', 'Done Terkirim',
    ];

    protected $table = 'permintaan_perangkat';

    protected $fillable = [
        'no_nota_dinas', 'tanggal_request', 'fungsi_requester', 'jumlah', 'keterangan',
        'status', 'catatan_admin', 'uker_kode', 'requested_by',
    ];

    protected $casts = [
        'tanggal_request' => 'date',
    ];

    protected $attributes = [
        'status' => 'Pending IT',
    ];

    public function uker()
    {
        return $this->belongsTo(Uker::class, 'uker_kode', 'kode');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
