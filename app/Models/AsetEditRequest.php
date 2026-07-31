<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsetEditRequest extends Model
{
    protected $fillable = [
        'aset_id', 'requested_by', 'alasan', 'status',
        'catatan_admin', 'handled_by', 'handled_at', 'sudah_dipakai',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
        'sudah_dipakai' => 'boolean',
    ];

    public function aset()
    {
        return $this->belongsTo(Aset::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
