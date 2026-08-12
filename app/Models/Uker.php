<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Uker extends Model
{
    use HasFactory;

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

    // Uker level KC (Kantor Cabang) ke atas (KANWIL, AREA, KC) -- gak
    // termasuk KCP/Unit/Lainnya. Dipakai khusus buat dropdown pilihan uker
    // di form Tambah/Edit Pekerja & Tambah/Edit User, karena yang punya akun
    // login cuma kantor cabang, jadi assign ke level KCP/Unit gak relevan
    // di 2 form itu. TIDAK dipakai di form Aset/Health Check/Uker (Cabang
    // Induk) -- tempat-tempat itu tetap harus bisa pilih semua level.
    public function scopeLevelKcKeAtas($query)
    {
        return $query->whereIn('jenis', ['KANWIL', 'AREA', 'KC']);
    }

    // Peta induk->anak (kode_spv -> [kode anak]) dari SELURUH uker -- satu
    // sumber kebenaran buat traversal tree, dipakai bareng oleh BuildsUkerTree
    // (rekap admin) dan descendantKodes() di bawah (scoping akses non-admin).
    public static function childrenMap(): array
    {
        $map = [];
        foreach (self::all(['kode', 'kode_spv']) as $u) {
            if ($u->kode_spv && $u->kode_spv != $u->kode) {
                $map[$u->kode_spv][] = $u->kode;
            }
        }

        return $map;
    }

    // Kode uker itu sendiri + SEMUA turunannya secara rekursif (anak, cucu,
    // dst -- bukan cuma anak langsung). Dipakai buat scoping akses role
    // "user": mereka boleh akses uker sendiri + seluruh cabang di bawahnya.
    public static function descendantKodes(int $ukerKode): array
    {
        $children = self::childrenMap();

        $hasil = [];
        $telusuri = function ($kode) use (&$telusuri, &$hasil, $children) {
            $hasil[] = $kode;
            foreach ($children[$kode] ?? [] as $anak) {
                $telusuri($anak);
            }
        };
        $telusuri($ukerKode);

        return $hasil;
    }
}
