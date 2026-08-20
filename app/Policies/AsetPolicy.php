<?php

namespace App\Policies;

use App\Models\Aset;
use App\Models\Uker;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AsetPolicy
{
    // Sama kayak update() -- boleh liat aset dari uker sendiri + turunannya
    // (bukan cuma yang boleh diedit), beda dari update() secara SEMANTIK
    // (lihat vs ubah) walau kebetulan cakupannya sama persis sekarang.
    public function view(User $user, Aset $aset): Response
    {
        return $user->role === 'admin' || in_array($aset->uker_kode, Uker::descendantKodes($user->uker_kode))
            ? Response::allow()
            : Response::deny('Anda tidak punya akses ke aset ini.');
    }

    public function update(User $user, Aset $aset): Response
    {
        return $user->role === 'admin' || in_array($aset->uker_kode, Uker::descendantKodes($user->uker_kode))
            ? Response::allow()
            : Response::deny('Anda tidak punya akses ke aset ini.');
    }

    public function delete(User $user, Aset $aset): Response
    {
        return $this->update($user, $aset);
    }

    public function restore(User $user, Aset $aset): Response
    {
        return $this->update($user, $aset);
    }

    // Dipakai di store()/update() buat validasi uker_kode yang diinput dari
    // form, bukan buat aset yang sudah ada di database. User boleh nunjuk uker
    // sendiri ATAU turunannya (anak/cucu di struktur organisasi), bukan cuma
    // uker sendiri persis.
    public function assignToUker(User $user, int $ukerKode): Response
    {
        return $user->role === 'admin' || in_array($ukerKode, Uker::descendantKodes($user->uker_kode))
            ? Response::allow()
            : Response::deny('Anda hanya bisa menambahkan/memindahkan aset ke uker Anda sendiri atau cabang di bawahnya.');
    }
}
