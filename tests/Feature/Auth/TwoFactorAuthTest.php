<?php

use App\Models\Uker;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

function kodeOtpValid(string $secret): string
{
    return (new Google2FA)->getCurrentOtp($secret);
}

test('user biasa gak diminta setup MFA sama sekali', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->be($user)->get(route('dashboard'))->assertOk();
});

test('admin baru dipaksa setup MFA dulu sebelum bisa akses halaman lain', function () {
    $admin = User::factory()->admin()->create();

    $this->be($admin)->get(route('dashboard'))->assertRedirect(route('two-factor.setup'));
    $this->be($admin)->get(route('users.index'))->assertRedirect(route('two-factor.setup'));
});

test('admin bisa lihat halaman setup & QR code-nya konsisten sepanjang sesi', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->be($admin)->get(route('two-factor.setup'));

    $response->assertOk();
    $secretPertama = session('mfa_setup_secret');
    expect($secretPertama)->not->toBeNull();

    // Buka halaman setup lagi sebelum konfirmasi -- secret-nya harus SAMA,
    // bukan digenerate ulang tiap kali (kalau beda, QR yang udah kesken duluan
    // bakal jadi gak valid lagi).
    $this->be($admin)->get(route('two-factor.setup'));
    expect(session('mfa_setup_secret'))->toBe($secretPertama);
});

test('konfirmasi setup MFA gagal kalau kode salah', function () {
    $admin = User::factory()->admin()->create();
    $this->be($admin)->get(route('two-factor.setup'));

    $response = $this->be($admin)->post(route('two-factor.confirm'), ['kode' => '000000']);

    $response->assertSessionHasErrors('kode');
    expect($admin->fresh()->mfaAktif())->toBeFalse();
});

test('konfirmasi setup MFA dengan kode benar mengaktifkan MFA & kasih recovery codes sekali doang', function () {
    $admin = User::factory()->admin()->create();
    $this->be($admin)->get(route('two-factor.setup'));
    $secret = session('mfa_setup_secret');

    $response = $this->be($admin)->post(route('two-factor.confirm'), ['kode' => kodeOtpValid($secret)]);

    $response->assertRedirect(route('two-factor.recovery-codes'));
    $admin->refresh();
    expect($admin->mfaAktif())->toBeTrue();
    expect($admin->two_factor_secret)->toBe($secret);
    expect($admin->two_factor_recovery_codes)->toHaveCount(8);

    // Halaman recovery codes cuma bisa ditampilin SEKALI (dibaca dari
    // session, bukan query ulang) -- kunjungan kedua harus udah gak ada lagi.
    $this->be($admin)->get(route('two-factor.recovery-codes'))->assertOk();
    $this->be($admin)->get(route('two-factor.recovery-codes'))->assertRedirect(route('dashboard'));
});

test('admin yang MFA-nya udah aktif tapi belum verifikasi di sesi ini diarahkan ke challenge, bukan setup', function () {
    $admin = User::factory()->admin()->create([
        'two_factor_secret' => (new Google2FA)->generateSecretKey(),
        'two_factor_confirmed_at' => now(),
    ]);

    $this->be($admin)->get(route('dashboard'))->assertRedirect(route('two-factor.challenge'));
});

test('verifikasi challenge dengan kode salah ditolak', function () {
    $admin = User::factory()->admin()->create([
        'two_factor_secret' => (new Google2FA)->generateSecretKey(),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->be($admin)->post(route('two-factor.verify'), ['kode' => '000000']);

    $response->assertSessionHasErrors('kode');
    $this->be($admin)->get(route('dashboard'))->assertRedirect(route('two-factor.challenge'));
});

test('verifikasi challenge dengan kode benar meloloskan akses buat sesi itu', function () {
    $secret = (new Google2FA)->generateSecretKey();
    $admin = User::factory()->admin()->create([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->be($admin)->post(route('two-factor.verify'), ['kode' => kodeOtpValid($secret)]);

    $response->assertRedirect(route('dashboard'));
    $this->be($admin)->withSession(['mfa_terverifikasi' => true])->get(route('dashboard'))->assertOk();
});

test('recovery code valid bisa dipakai gantiin kode authenticator, tapi cuma sekali', function () {
    $admin = User::factory()->admin()->create([
        'two_factor_secret' => (new Google2FA)->generateSecretKey(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['ABCDE-FGHJK', 'KLMNO-PQRST'],
    ]);

    $response = $this->be($admin)->post(route('two-factor.verify'), ['kode' => 'ABCDE-FGHJK']);
    $response->assertRedirect(route('dashboard'));
    expect($admin->fresh()->two_factor_recovery_codes)->toBe(['KLMNO-PQRST']);

    // Kode yang sama gak boleh dipakai dua kali
    $admin->refresh();
    $response = $this->be($admin)->post(route('two-factor.verify'), ['kode' => 'ABCDE-FGHJK']);
    $response->assertSessionHasErrors('kode');
});

test('command mfa:reset ngosongin MFA user & minta konfirmasi', function () {
    $admin = User::factory()->admin()->create([
        'two_factor_secret' => (new Google2FA)->generateSecretKey(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['ABCDE-FGHJK'],
    ]);

    $this->artisan('mfa:reset', ['pn' => $admin->pn])
        ->expectsConfirmation("Reset MFA buat {$admin->name} ({$admin->pn})? Dia bakal diminta setup ulang dari awal (scan QR baru) pas login berikutnya.", 'yes')
        ->assertExitCode(0);

    $admin->refresh();
    expect($admin->mfaAktif())->toBeFalse();
    expect($admin->two_factor_secret)->toBeNull();
    expect($admin->two_factor_recovery_codes)->toBeNull();
});
