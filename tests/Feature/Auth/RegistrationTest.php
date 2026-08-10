<?php

// Self-register dimatikan -- akun cuma boleh dibuat admin lewat Kelola User.
// Route GET /register sengaja tetap ada tapi cuma redirect ke login, dan
// POST /register gak lagi terdaftar sama sekali.

test('halaman register redirect ke login, gak lagi bisa diakses', function () {
    $response = $this->get('/register');

    $response->assertRedirect(route('login'));
});

test('post ke /register gak bisa dipakai buat bikin akun baru', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertMethodNotAllowed();
    $this->assertGuest();
});
