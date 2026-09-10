<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

// Satu-satunya jalan darurat kalau admin kehilangan HP authenticator-nya
// SEKALIGUS kehilangan recovery codes -- sengaja gak ada tombol reset MFA
// di UI (misal di Kelola User), biar gak ada celah "admin lain bisa cabut
// MFA admin lain lewat web" tanpa akses server. Lewat SSH/terminal server
// aja, dan wajib diketik ulang PN + konfirmasi biar gak kepencet gak sengaja.
#[Signature('mfa:reset {pn : PN akun yang MFA-nya mau direset}')]
#[Description('Reset MFA (2FA) satu akun admin -- dipakai kalau HP authenticator-nya hilang/ganti dan recovery codes juga udah abis/hilang')]
class ResetMfaCommand extends Command
{
    public function handle(): int
    {
        $user = User::where('pn', $this->argument('pn'))->first();

        if (! $user) {
            $this->error("Gak ada user dengan PN {$this->argument('pn')}.");

            return self::FAILURE;
        }

        if (! $user->mfaAktif()) {
            $this->info("MFA akun {$user->name} ({$user->pn}) memang belum aktif, gak ada yang perlu direset.");

            return self::SUCCESS;
        }

        if (! $this->confirm("Reset MFA buat {$user->name} ({$user->pn})? Dia bakal diminta setup ulang dari awal (scan QR baru) pas login berikutnya.")) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->info("MFA {$user->name} ({$user->pn}) berhasil direset. Dia bakal diminta setup ulang pas login berikutnya.");

        return self::SUCCESS;
    }
}
