<?php

namespace App\Models;

// Illuminate\Foundation\Auth\User as Authenticatable
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'pn',
        'password',
        'role',
        'uker_kode',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ukerRelasi()
    {
        return $this->belongsTo(Uker::class, 'uker_kode', 'kode');
    }

    public function pekerja()
    {
        return $this->belongsTo(Pekerja::class, 'pn', 'pn');
    }

    // Jabatan diambil otomatis dari data pekerja yang nempel ke PN,
    // bukan disimpan manual -- biar selalu sinkron sama data master
    public function getJabatanAttribute(): ?string
    {
        return $this->pekerja?->jabatan;
    }
}
