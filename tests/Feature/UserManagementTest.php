<?php

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

    $this->actingAs($admin)->get(route('users.index'))->assertOk();

    $store = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Budi',
        'email' => 'budi@example.com',
        'password' => 'password123',
        'role' => 'user',
        'uker_kode' => $uker->kode,
    ]);
    $store->assertRedirect(route('users.index'));

    $budi = User::where('email', 'budi@example.com')->first();
    expect($budi)->not->toBeNull();
    expect($budi->role)->toBe('user');
    expect($budi->uker_kode)->toBe($uker->kode);

    $update = $this->actingAs($admin)->put(route('users.update', $budi), [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'role' => 'admin',
        'uker_kode' => '',
    ]);
    $update->assertRedirect(route('users.index'));
    expect($budi->fresh()->name)->toBe('Budi Santoso');
    expect($budi->fresh()->role)->toBe('admin');
    expect($budi->fresh()->uker_kode)->toBeNull();
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
