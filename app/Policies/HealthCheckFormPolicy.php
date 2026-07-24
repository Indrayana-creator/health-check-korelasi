<?php

namespace App\Policies;

use App\Models\HealthCheckForm;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class HealthCheckFormPolicy
{
    public function update(User $user, HealthCheckForm $healthcheck): Response
    {
        return $user->role === 'admin' || $healthcheck->uker_kode === $user->uker_kode
            ? Response::allow()
            : Response::deny('Anda tidak punya akses ke form ini.');
    }

    public function delete(User $user, HealthCheckForm $healthcheck): Response
    {
        return $this->update($user, $healthcheck);
    }

    // Dipakai di store() buat validasi uker_kode yang diinput dari form,
    // bukan buat form yang sudah ada di database.
    public function assignToUker(User $user, int $ukerKode): Response
    {
        return $user->role === 'admin' || $ukerKode === $user->uker_kode
            ? Response::allow()
            : Response::deny('Anda hanya bisa membuat form health check untuk uker Anda sendiri.');
    }
}
