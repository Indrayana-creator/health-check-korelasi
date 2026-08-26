<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Simulasikan device lain yang masih login -- baris mentah di tabel
// sessions, independen dari SESSION_DRIVER yang lagi aktif buat testing
// (array), karena LoginRequest::sesiLainAktif() query tabel ini langsung
// lewat DB::table(), bukan lewat session driver.
function buatSesiLain(int $userId, int $menitLalu = 5): string
{
    $id = 'sesi-device-lain-'.uniqid();
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $userId,
        'ip_address' => '10.0.0.99',
        'user_agent' => 'Test Device Lain',
        'payload' => base64_encode(serialize([])),
        'last_activity' => now()->subMinutes($menitLalu)->timestamp,
    ]);

    return $id;
}

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'pn' => $user->pn,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'pn' => $user->pn,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('login kena minta konfirmasi kalau akun masih aktif di sesi lain', function () {
    $user = User::factory()->create();
    buatSesiLain($user->id);

    $response = $this->post('/login', [
        'pn' => $user->pn,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHas('sesi_aktif_token');
    $response->assertSessionHas('sesi_aktif_sejak');
});

test('sesi lain yang udah basi (lebih lama dari SESSION_LIFETIME) gak dianggap konflik', function () {
    $user = User::factory()->create();
    buatSesiLain($user->id, menitLalu: (int) config('session.lifetime') + 10);

    $response = $this->post('/login', [
        'pn' => $user->pn,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('konfirmasi lanjut login ngehapus sesi lain dan nyelesain login tanpa password lagi', function () {
    $user = User::factory()->create();
    $sesiLainId = buatSesiLain($user->id);

    $this->post('/login', [
        'pn' => $user->pn,
        'password' => 'password',
    ]);
    $token = session('sesi_aktif_token');
    expect($token)->not->toBeNull();

    $response = $this->post(route('login.confirm'), ['token' => $token]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    expect(DB::table('sessions')->where('id', $sesiLainId)->exists())->toBeFalse();
});

test('konfirmasi login dengan token gak valid ditolak', function () {
    $response = $this->post(route('login.confirm'), ['token' => 'token-ngasal']);

    $this->assertGuest();
    $response->assertSessionHasErrors('pn');
});

test('konfirmasi login cuma bisa dipakai sekali (token sekali-pakai)', function () {
    $user = User::factory()->create();
    buatSesiLain($user->id);

    $this->post('/login', ['pn' => $user->pn, 'password' => 'password']);
    $token = session('sesi_aktif_token');

    $this->post(route('login.confirm'), ['token' => $token]);
    $this->post('/logout');

    $response = $this->post(route('login.confirm'), ['token' => $token]);

    $this->assertGuest();
    $response->assertSessionHasErrors('pn');
});
