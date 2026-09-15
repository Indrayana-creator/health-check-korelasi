<?php

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Semua test lain yang pakai actingAs() gak ada hubungannya sama MFA
    // (mereka nguji fitur X, bukan alur login) -- daripada tiap test admin
    // di seluruh suite disuruh urus setup MFA dulu, override ini otomatis
    // nganggep admin yang dipakai actingAs() udah lewat MFA. Behavior MFA
    // yang SEBENARNYA (gerbang setup/challenge) diuji terpisah & eksplisit
    // di TwoFactorAuthTest pakai $this->be() langsung (bukan actingAs()),
    // biar override ini gak ikut nge-bypass yang justru lagi diuji di sana.
    //
    // Sama alasannya buat Admin Master: mayoritas test yang pakai admin()
    // nguji fitur admin pada umumnya, bukan beda admin biasa vs Admin
    // Master -- jadi default-nya admin test dianggap Admin Master juga.
    // Behavior beda-tingkatnya diuji terpisah & eksplisit di
    // AdminMasterTest pakai $this->be() langsung.
    public function actingAs(Authenticatable $user, $guard = null)
    {
        if ($user instanceof User && $user->role === 'admin') {
            if (! $user->mfaAktif()) {
                $user->forceFill(['two_factor_confirmed_at' => now()])->save();
            }
            if (! $user->is_admin_master) {
                $user->forceFill(['is_admin_master' => true])->save();
            }
        }

        $this->withSession(['mfa_terverifikasi' => true]);

        return parent::actingAs($user, $guard);
    }
}
