<?php

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
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

test('login gak nyimpen remember_token walau field remember dikirim manual, biar gak bisa bypass 1-sesi-aktif lewat cookie recaller', function () {
    // remember_token dipaksa null dulu -- factory User isi field ini dengan
    // string acak by default (gak ada hubungannya sama proses login), jadi
    // kalau gak di-null-kan dulu, test ini gak beneran ngetes apa-apa.
    $user = User::factory()->create(['remember_token' => null]);

    $this->post('/login', [
        'pn' => $user->pn,
        'password' => 'password',
        'remember' => '1',
    ]);

    $this->assertAuthenticated();
    expect($user->fresh()->remember_token)->toBeNull();
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

// ===================== Login Log (audit trail) =====================

test('login berhasil kecatat di login_logs', function () {
    $user = User::factory()->create();

    $this->post('/login', ['pn' => $user->pn, 'password' => 'password']);

    $log = LoginLog::latest('id')->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe(LoginLog::STATUS_BERHASIL);
    expect($log->user_id)->toBe($user->id);
    expect($log->pn_dicoba)->toBe($user->pn);
});

test('login gagal karena password salah kecatat sebagai gagal_kredensial, tetap kesimpen user_id-nya', function () {
    $user = User::factory()->create();

    $this->post('/login', ['pn' => $user->pn, 'password' => 'salah-banget']);

    $log = LoginLog::latest('id')->first();
    expect($log->status)->toBe(LoginLog::STATUS_GAGAL_KREDENSIAL);
    expect($log->user_id)->toBe($user->id);
});

test('login gagal karena PN gak ketemu tetap kecatat, user_id-nya null', function () {
    $this->post('/login', ['pn' => '99999999', 'password' => 'apapun']);

    $log = LoginLog::latest('id')->first();
    expect($log->status)->toBe(LoginLog::STATUS_GAGAL_KREDENSIAL);
    expect($log->user_id)->toBeNull();
    expect($log->pn_dicoba)->toBe('99999999');
});

test('login akun nonaktif kecatat sebagai gagal_nonaktif', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->post('/login', ['pn' => $user->pn, 'password' => 'password']);

    $log = LoginLog::latest('id')->first();
    expect($log->status)->toBe(LoginLog::STATUS_GAGAL_NONAKTIF);
    expect($log->user_id)->toBe($user->id);
});

test('login yang ditolak karena sesi lain aktif kecatat, lalu konfirmasi lanjut kecatat lagi sebagai berhasil', function () {
    $user = User::factory()->create();
    buatSesiLain($user->id);

    $this->post('/login', ['pn' => $user->pn, 'password' => 'password']);
    $token = session('sesi_aktif_token');

    expect(LoginLog::where('status', LoginLog::STATUS_DITOLAK_SESI_LAIN)->where('user_id', $user->id)->exists())->toBeTrue();

    $this->post(route('login.confirm'), ['token' => $token]);

    expect(LoginLog::where('status', LoginLog::STATUS_BERHASIL)->where('user_id', $user->id)->count())->toBe(1);
});

test('user biasa tidak bisa akses login history', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('login-history.index'))->assertForbidden();
});

test('admin bisa lihat login history dan filter berdasarkan status', function () {
    $admin = User::factory()->admin()->create();
    LoginLog::create(['user_id' => $admin->id, 'pn_dicoba' => $admin->pn, 'status' => LoginLog::STATUS_BERHASIL, 'ip_address' => '127.0.0.1']);
    LoginLog::create(['user_id' => $admin->id, 'pn_dicoba' => $admin->pn, 'status' => LoginLog::STATUS_GAGAL_KREDENSIAL, 'ip_address' => '127.0.0.1']);

    $response = $this->actingAs($admin)->get(route('login-history.index', ['status' => LoginLog::STATUS_GAGAL_KREDENSIAL]));

    $response->assertOk();
    $logs = $response->viewData('logs');
    expect($logs->total())->toBe(1);
    expect($logs->first()->status)->toBe(LoginLog::STATUS_GAGAL_KREDENSIAL);
});

test('halaman error 403 gak crash kalau dipicu buat guest (belum login)', function () {
    // Simulasikan skenario: middleware/route lain suatu saat abort(403) buat
    // request TANPA user sama sekali (bukan cuma role salah) -- errors/403.blade.php
    // pakai <x-app-layout> yang baca auth()->user()->role tanpa null-guard,
    // jadi kalau view ini gak nge-guard sendiri, ini bakal fatal error kalau
    // dirender buat guest.
    $exception = new AuthorizationException('Pesan tes akses ditolak.');

    $html = view('errors.403', ['exception' => $exception])->render();

    expect($html)->toContain('Bukan Wewenang Anda');
    expect($html)->toContain('Pesan tes akses ditolak.');
    expect($html)->toContain('Ke Halaman Login');
});
