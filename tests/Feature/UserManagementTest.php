<?php

use App\Models\Pekerja;
use App\Models\Uker;
use App\Models\User;

test('guest tidak bisa akses kelola user', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

test('user biasa tidak bisa akses kelola user', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('users.index'))->assertForbidden();
});

test('admin bisa melihat, menambah, dan mengedit user', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $pekerja = Pekerja::factory()->create();

    $this->actingAs($admin)->get(route('users.index'))->assertOk();

    $store = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Budi',
        'email' => 'budi@example.com',
        'pn' => $pekerja->pn,
        'password' => 'password123',
        'role' => 'user',
        'uker_kode' => $uker->kode,
    ]);
    $store->assertRedirect(route('users.index'));

    $budi = User::where('email', 'budi@example.com')->first();
    expect($budi)->not->toBeNull();
    expect($budi->role)->toBe('user');
    expect($budi->uker_kode)->toBe($uker->kode);
    expect($budi->pn)->toBe($pekerja->pn);

    $update = $this->actingAs($admin)->put(route('users.update', $budi), [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'pn' => $pekerja->pn,
        'role' => 'admin',
        'uker_kode' => '',
    ]);
    $update->assertRedirect(route('users.index'));
    expect($budi->fresh()->name)->toBe('Budi Santoso');
    expect($budi->fresh()->role)->toBe('admin');
    expect($budi->fresh()->uker_kode)->toBeNull();
});

test('pn wajib diisi dan harus terdaftar di data pekerja saat menambah user', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();

    $kosong = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Tanpa PN',
        'email' => 'tanpapn@example.com',
        'password' => 'password123',
        'role' => 'user',
        'uker_kode' => $uker->kode,
    ]);
    $kosong->assertSessionHasErrors('pn');

    $tidakTerdaftar = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'PN Ngasal',
        'email' => 'pnngasal@example.com',
        'pn' => '99999999',
        'password' => 'password123',
        'role' => 'user',
        'uker_kode' => $uker->kode,
    ]);
    $tidakTerdaftar->assertSessionHasErrors('pn');

    expect(User::where('email', 'tanpapn@example.com')->exists())->toBeFalse();
    expect(User::where('email', 'pnngasal@example.com')->exists())->toBeFalse();
});

test('admin bisa menghapus user lain tapi tidak bisa menghapus akun sendiri', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $lainnya = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($admin)->delete(route('users.destroy', $lainnya))->assertRedirect(route('users.index'));
    expect(User::find($lainnya->id))->toBeNull();

    $this->actingAs($admin)->delete(route('users.destroy', $admin))->assertForbidden();
    expect(User::find($admin->id))->not->toBeNull();
});

test('admin bisa menonaktifkan dan mengaktifkan lagi user lain, tapi tidak bisa menonaktifkan diri sendiri', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $lainnya = User::factory()->forUker($uker->kode)->create();
    expect($lainnya->is_active)->toBeTrue();

    $this->actingAs($admin)->post(route('users.toggleActive', $lainnya))->assertRedirect();
    expect($lainnya->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)->post(route('users.toggleActive', $lainnya))->assertRedirect();
    expect($lainnya->fresh()->is_active)->toBeTrue();

    $this->actingAs($admin)->post(route('users.toggleActive', $admin))->assertForbidden();
    expect($admin->fresh()->is_active)->toBeTrue();
});

test('user yang dinonaktifkan tidak bisa login', function () {
    $user = User::factory()->create(['password' => bcrypt('password123'), 'is_active' => false]);

    $response = $this->post(route('login'), [
        'pn' => $user->pn,
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('pn');
    $this->assertGuest();
});

test('user aktif tetap bisa login seperti biasa', function () {
    $user = User::factory()->create(['password' => bcrypt('password123'), 'is_active' => true]);

    $response = $this->post(route('login'), [
        'pn' => $user->pn,
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
});
