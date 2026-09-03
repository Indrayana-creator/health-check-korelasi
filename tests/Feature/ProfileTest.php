<?php

use App\Models\LoginLog;
use App\Models\Uker;
use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('halaman profile nampilin PN & uker sendiri, read-only', function () {
    $uker = Uker::factory()->create(['nama' => 'KC Contoh']);
    $user = User::factory()->forUker($uker->kode)->create();

    $response = $this->actingAs($user)->get('/profile');

    $response->assertOk();
    $response->assertSee($user->pn);
    $response->assertSee('KC Contoh');
});

test('halaman profile nampilin login history milik sendiri, bukan punya user lain', function () {
    $user = User::factory()->create();
    $userLain = User::factory()->create();
    LoginLog::create(['user_id' => $user->id, 'pn_dicoba' => $user->pn, 'status' => LoginLog::STATUS_BERHASIL, 'ip_address' => '10.0.0.1']);
    LoginLog::create(['user_id' => $userLain->id, 'pn_dicoba' => $userLain->pn, 'status' => LoginLog::STATUS_BERHASIL, 'ip_address' => '10.0.0.2']);

    $response = $this->actingAs($user)->get('/profile');

    $response->assertOk();
    $response->assertSee('10.0.0.1');
    $response->assertDontSee('10.0.0.2');
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user gak bisa hapus akun sendiri lewat rute /profile DELETE -- fitur ini sengaja dihapus', function () {
    // Breeze bawaan ngasih user hapus akun sendiri tanpa approval admin --
    // gak cocok buat sistem internal yang PN-nya jadi acuan di banyak
    // tempat (Health Check, Log History, dst). Penghapusan akun sekarang
    // cuma lewat UserController::destroy() (admin only).
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete('/profile', ['password' => 'password']);

    $response->assertMethodNotAllowed();
    $this->assertNotNull($user->fresh());
});
