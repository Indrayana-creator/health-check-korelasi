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

    // Default in-memory sebelum di-save, biar konsisten sama default kolom
    // DB-nya (is_active) -- kepake misal ada kode yang baca $user->is_active
    // dari instance yang baru dibikin tapi belum di-refresh dari DB.
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'is_active' => 'boolean',
            'is_admin_master' => 'boolean',
            // 'encrypted' -- secret & recovery codes gak boleh kebaca
            // plaintext walau database-nya bocor. Sengaja gak masuk
            // $fillable, cuma di-set manual lewat TwoFactorController.
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
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

    public function perubahanLogs()
    {
        return $this->hasMany(UserPerubahanLog::class)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    // MFA cuma diwajibkan buat admin -- akun ini yang paling luas aksesnya
    // (approve semua permintaan, kelola user lain, lihat data sensitif),
    // jadi paling perlu lapisan keamanan tambahan. User cabang biasa gak
    // diwajibkan biar gak nambah friksi ke ratusan akun sekaligus.
    public function wajibMfa(): bool
    {
        return $this->role === 'admin';
    }

    public function mfaAktif(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }

    // Admin Master = satu-satunya tingkatan yang boleh menghapus apa pun
    // secara permanen (aset, health check, user, data master Uker/Kode
    // Aset/Pekerja) atau approve Permintaan Hapus Aset. Admin biasa tetap
    // bisa kelola & approve edit, tapi buat hapus harus lewat Admin Master
    // -- sama kayak alur yang berlaku buat cabang. Sengaja bukan role
    // terpisah (masih role=admin), cuma flag tambahan, biar semua
    // pengecekan "role === 'admin'" yang sudah ada (akses halaman admin,
    // dst) gak perlu diubah sama sekali.
    public function isAdminMaster(): bool
    {
        return $this->role === 'admin' && $this->is_admin_master;
    }

    // Jabatan diambil otomatis dari data pekerja yang nempel ke PN,
    // bukan disimpan manual -- biar selalu sinkron sama data master
    public function getJabatanAttribute(): ?string
    {
        return $this->pekerja?->jabatan;
    }
}
