<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthCheckItem extends Model
{
    protected $fillable = ['health_check_form_id', 'kategori', 'item_pemeriksaan', 'status', 'catatan'];

    public function form()
    {
        return $this->belongsTo(HealthCheckForm::class, 'health_check_form_id');
    }
}
