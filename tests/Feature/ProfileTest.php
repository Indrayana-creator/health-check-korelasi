<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
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
