<?php

namespace App\Policies;

use App\Models\Aset;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AsetPolicy
{
    public function update(User $user, Aset $aset): Response
    {
        return $user->role === 'admin' || $aset->uker_kode === $user->uker_kode
            ? Response::allow()
            : Response::deny('Anda tidak punya akses ke aset ini.');
    }

    public function delete(User $user, Aset $aset): Response
    {
        return $this->update($user, $aset);
    }

    // Dipakai di store()/update() buat validasi uker_kode yang diinput dari
    // form, bukan buat aset yang sudah ada di database.
    public function assignToUker(User $user, int $ukerKode): Response
    {
        return $user->role === 'admin' || $ukerKode === $user->uker_kode
            ? Response::allow()
            : Response::deny('Anda hanya bisa menambahkan/memindahkan aset ke uker Anda sendiri.');
    }
}
