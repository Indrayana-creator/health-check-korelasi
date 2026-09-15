<?php

use App\Models\Aset;
use App\Models\AsetHapusRequest;
use App\Models\HealthCheckForm;
use App\Models\KodeAset;
use App\Models\Pekerja;
use App\Models\Uker;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;

function buatAdminMaster(): User
{
    return User::factory()->admin()->create(['is_admin_master' => true]);
}

function buatAdminBiasa(): User
{
    return User::factory()->admin()->create(['is_admin_master' => false]);
}

// masuk() dipakai (bukan actingAs()) justru biar TestCase::actingAs()
// override (yang otomatis nge-set semua admin jadi Admin Master, lihat
// tests/TestCase.php) gak ikut nge-bypass yang lagi diuji di file ini.
// Tapi itu artinya MFA juga harus dipuaskan manual di sini, biar
// EnsureAdminTwoFactor gak nge-redirect ke /two-factor/setup duluan
// sebelum sempet nyentuh behavior Admin Master yang mau diuji.
function masuk(User $user): TestCase
{
    if ($user->role === 'admin' && ! $user->mfaAktif()) {
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
    }

    test()->withSession(['mfa_terverifikasi' => true]);

    return test()->be($user);
}

test('isAdminMaster() cuma true kalau role admin DAN flag-nya aktif', function () {
    $adminMaster = buatAdminMaster();
    $adminBiasa = buatAdminBiasa();
    $userFlagNyasar = User::factory()->create(['is_admin_master' => true]);

    expect($adminMaster->isAdminMaster())->toBeTrue();
    expect($adminBiasa->isAdminMaster())->toBeFalse();
    expect($userFlagNyasar->isAdminMaster())->toBeFalse();
});

// ===================== Aset =====================

test('admin biasa gak bisa hapus aset langsung, harus lewat Permintaan Hapus', function () {
    $adminBiasa = buatAdminBiasa();
    $uker = Uker::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode]);

    masuk($adminBiasa)->delete(route('aset.destroy', $aset))->assertForbidden();
    expect(Aset::find($aset->id))->not->toBeNull();
});

test('admin biasa bisa hapus aset kalau Admin Master sudah approve permintaannya', function () {
    $adminBiasa = buatAdminBiasa();
    $adminMaster = buatAdminMaster();
    $uker = Uker::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode]);

    masuk($adminBiasa)->post(route('aset.requestDelete', $aset), ['alasan' => 'Rusak'])
        ->assertRedirect(route('aset.show', $aset));
    $hapusRequest = AsetHapusRequest::first();

    masuk($adminMaster)->post(route('aset.hapusRequests.approve', $hapusRequest))->assertRedirect();

    masuk($adminBiasa)->delete(route('aset.destroy', $aset))->assertRedirect(route('aset.index'));
    expect(Aset::find($aset->id))->toBeNull();
});

test('admin Master bisa hapus aset langsung tanpa permintaan', function () {
    $adminMaster = buatAdminMaster();
    $uker = Uker::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode]);

    masuk($adminMaster)->delete(route('aset.destroy', $aset))->assertRedirect(route('aset.index'));
    expect(Aset::find($aset->id))->toBeNull();
});

test('admin biasa gak bisa approve atau tolak Permintaan Hapus Aset', function () {
    $adminBiasa = buatAdminBiasa();
    $uker = Uker::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode]);
    $hapusRequest = AsetHapusRequest::create(['aset_id' => $aset->id, 'requested_by' => $adminBiasa->id, 'status' => 'Menunggu']);

    masuk($adminBiasa)->post(route('aset.hapusRequests.approve', $hapusRequest))->assertForbidden();
    masuk($adminBiasa)->post(route('aset.hapusRequests.reject', $hapusRequest), ['catatan_admin' => 'x'])->assertForbidden();
    expect($hapusRequest->fresh()->status)->toBe('Menunggu');
});

test('admin biasa gak bisa akses Delete Massal Aset, Admin Master bisa', function () {
    $adminBiasa = buatAdminBiasa();
    $adminMaster = buatAdminMaster();

    masuk($adminBiasa)->get(route('aset.bulkDeleteForm'))->assertForbidden();
    masuk($adminMaster)->get(route('aset.bulkDeleteForm'))->assertOk();
});

// ===================== Health Check =====================

test('admin biasa gak bisa hapus form Health Check, Admin Master bisa', function () {
    $adminBiasa = buatAdminBiasa();
    $adminMaster = buatAdminMaster();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);

    masuk($adminBiasa)->delete(route('healthcheck.destroy', $form))->assertForbidden();
    expect(HealthCheckForm::find($form->id))->not->toBeNull();

    masuk($adminMaster)->delete(route('healthcheck.destroy', $form))->assertRedirect(route('healthcheck.index'));
    expect(HealthCheckForm::find($form->id))->toBeNull();
});

test('admin biasa gak bisa akses Delete Massal Health Check, Admin Master bisa', function () {
    $adminBiasa = buatAdminBiasa();
    $adminMaster = buatAdminMaster();

    masuk($adminBiasa)->get(route('healthcheck.bulkDeleteForm'))->assertForbidden();
    masuk($adminMaster)->get(route('healthcheck.bulkDeleteForm'))->assertOk();
});

test('user biasa (non-admin) tetap gak bisa akses Delete Massal Health Check sama sekali', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    masuk($user)->get(route('healthcheck.bulkDeleteForm'))->assertForbidden();
    masuk($user)->post(route('healthcheck.bulkDelete'))->assertForbidden();
});

// ===================== User =====================

test('admin biasa gak bisa hapus akun user, Admin Master bisa', function () {
    $adminBiasa = buatAdminBiasa();
    $adminMaster = buatAdminMaster();
    $uker = Uker::factory()->create();
    $target = User::factory()->forUker($uker->kode)->create();

    masuk($adminBiasa)->delete(route('users.destroy', $target))->assertForbidden();
    expect(User::find($target->id))->not->toBeNull();

    masuk($adminMaster)->delete(route('users.destroy', $target))->assertRedirect(route('users.index'));
    expect(User::find($target->id))->toBeNull();
});

test('admin biasa gak bisa jadiin dirinya sendiri atau admin lain jadi Admin Master lewat form', function () {
    $adminBiasa = buatAdminBiasa();
    $uker = Uker::factory()->create();
    $targetAdmin = User::factory()->admin()->create(['is_admin_master' => false]);

    masuk($adminBiasa)->put(route('users.update', $targetAdmin), [
        'name' => $targetAdmin->name,
        'pn' => $targetAdmin->pn,
        'role' => 'admin',
        'is_admin_master' => '1',
    ])->assertRedirect(route('users.index'));

    expect($targetAdmin->fresh()->is_admin_master)->toBeFalse();
});

test('Admin Master bisa jadiin admin lain jadi Admin Master lewat form', function () {
    $adminMaster = buatAdminMaster();
    $targetAdmin = User::factory()->admin()->create(['is_admin_master' => false]);

    masuk($adminMaster)->put(route('users.update', $targetAdmin), [
        'name' => $targetAdmin->name,
        'pn' => $targetAdmin->pn,
        'role' => 'admin',
        'is_admin_master' => '1',
    ])->assertRedirect(route('users.index'));

    expect($targetAdmin->fresh()->is_admin_master)->toBeTrue();
});

test('status Admin Master otomatis lepas kalau role diturunin jadi user', function () {
    $adminMaster = buatAdminMaster();
    $targetAdmin = User::factory()->admin()->create(['is_admin_master' => true]);
    $uker = Uker::factory()->create();

    masuk($adminMaster)->put(route('users.update', $targetAdmin), [
        'name' => $targetAdmin->name,
        'pn' => $targetAdmin->pn,
        'role' => 'user',
        'uker_kode' => $uker->kode,
    ])->assertRedirect(route('users.index'));

    $targetAdmin->refresh();
    expect($targetAdmin->role)->toBe('user');
    expect($targetAdmin->is_admin_master)->toBeFalse();
});

// ===================== Data Master (Uker, Kode Aset, Pekerja) =====================

test('admin biasa gak bisa hapus Uker, Admin Master bisa', function () {
    $adminBiasa = buatAdminBiasa();
    $adminMaster = buatAdminMaster();
    $uker = Uker::factory()->create();

    masuk($adminBiasa)->delete(route('ukers.destroy', $uker))->assertForbidden();
    expect(Uker::find($uker->id))->not->toBeNull();

    masuk($adminMaster)->delete(route('ukers.destroy', $uker))->assertRedirect(route('ukers.index'));
    expect(Uker::find($uker->id))->toBeNull();
});

test('admin biasa gak bisa hapus Kode Aset, Admin Master bisa', function () {
    $adminBiasa = buatAdminBiasa();
    $adminMaster = buatAdminMaster();
    $kodeAset = KodeAset::factory()->create();

    masuk($adminBiasa)->delete(route('kode-aset.destroy', $kodeAset))->assertForbidden();
    expect(KodeAset::find($kodeAset->kode))->not->toBeNull();

    masuk($adminMaster)->delete(route('kode-aset.destroy', $kodeAset))->assertRedirect(route('kode-aset.index'));
    expect(KodeAset::find($kodeAset->kode))->toBeNull();
});

test('admin biasa gak bisa hapus Pekerja, Admin Master bisa', function () {
    $adminBiasa = buatAdminBiasa();
    $adminMaster = buatAdminMaster();
    $pekerja = Pekerja::factory()->create();

    masuk($adminBiasa)->delete(route('pekerja.destroy', $pekerja))->assertForbidden();
    expect(Pekerja::find($pekerja->pn))->not->toBeNull();

    masuk($adminMaster)->delete(route('pekerja.destroy', $pekerja))->assertRedirect(route('pekerja.index'));
    expect(Pekerja::find($pekerja->pn))->toBeNull();
});
