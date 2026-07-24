<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function delete(User $authUser, User $target): Response
    {
        return $authUser->id !== $target->id
            ? Response::allow()
            : Response::deny('Anda tidak bisa menghapus akun Anda sendiri.');
    }
}
