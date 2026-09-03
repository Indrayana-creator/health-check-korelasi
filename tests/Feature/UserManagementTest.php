<?php

use App\Models\LoginLog;
use App\Models\Pekerja;
use App\Models\Uker;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
        'pn' => $pekerja->pn,
        'password' => 'password123',
        'role' => 'user',
        'uker_kode' => $uker->kode,
    ]);
    $store->assertRedirect(route('users.index'));

    $budi = User::where('pn', $pekerja->pn)->first();
    expect($budi)->not->toBeNull();
    expect($budi->role)->toBe('user');
    expect($budi->uker_kode)->toBe($uker->kode);
    expect($budi->pn)->toBe($pekerja->pn);

    $update = $this->actingAs($admin)->put(route('users.update', $budi), [
        'name' => 'Budi Santoso',
        'pn' => $pekerja->pn,
        'role' => 'admin',
        'uker_kode' => '',
    ]);
    $update->assertRedirect(route('users.index'));
    expect($budi->fresh()->name)->toBe('Budi Santoso');
    expect($budi->fresh()->role)->toBe('admin');
    expect($budi->fresh()->uker_kode)->toBeNull();
});

test('update user yang ganti role & uker otomatis kecatat di riwayat perubahan', function () {
    $admin = User::factory()->admin()->create();
    $ukerLama = Uker::factory()->create();
    $pekerja = Pekerja::factory()->create();
    $budi = User::factory()->create(['pn' => $pekerja->pn, 'name' => 'Budi', 'role' => 'user', 'uker_kode' => $ukerLama->kode]);

    $this->actingAs($admin)->put(route('users.update', $budi), [
        'name' => 'Budi',
        'pn' => $pekerja->pn,
        'role' => 'admin',
        'uker_kode' => '',
    ]);

    $logs = $budi->fresh()->perubahanLogs;
    expect($logs)->toHaveCount(2); // role & uker_kode berubah, name gak berubah

    $logRole = $logs->firstWhere('field', 'role');
    expect($logRole->nilai_lama)->toBe('user');
    expect($logRole->nilai_baru)->toBe('admin');
    expect($logRole->changed_by)->toBe($admin->id);

    $logUker = $logs->firstWhere('field', 'uker_kode');
    expect((int) $logUker->nilai_lama)->toBe($ukerLama->kode);
    expect($logUker->nilai_baru)->toBeNull();
});

test('update user tanpa ganti apapun gak nambah riwayat perubahan', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $pekerja = Pekerja::factory()->create();
    $budi = User::factory()->create(['pn' => $pekerja->pn, 'name' => 'Budi', 'role' => 'user', 'uker_kode' => $uker->kode]);

    $this->actingAs($admin)->put(route('users.update', $budi), [
        'name' => 'Budi',
        'pn' => $pekerja->pn,
        'role' => 'user',
        'uker_kode' => $uker->kode,
    ]);

    expect($budi->fresh()->perubahanLogs)->toHaveCount(0);
});

test('nonaktifkan/aktifkan user kecatat di riwayat perubahan sebagai field is_active', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($admin)->post(route('users.toggleActive', $user));

    $log = $user->fresh()->perubahanLogs->first();
    expect($log->field)->toBe('is_active');
    expect($log->nilai_lama)->toBe('1');
    expect($log->nilai_baru)->toBe('0');
});

test('riwayat perubahan user tampil di halaman edit', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $pekerja = Pekerja::factory()->create();
    $budi = User::factory()->create(['pn' => $pekerja->pn, 'name' => 'Budi', 'role' => 'user', 'uker_kode' => $uker->kode]);

    $this->actingAs($admin)->put(route('users.update', $budi), [
        'name' => 'Budi', 'pn' => $pekerja->pn, 'role' => 'admin', 'uker_kode' => '',
    ]);

    $response = $this->actingAs($admin)->get(route('users.edit', $budi));

    $response->assertOk();
    $response->assertSee('Role');
    $response->assertSee('Admin');
});

test('kolom Login Terakhir di Kelola User nunjukin waktu login berhasil paling baru', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->travelTo(now()->subDays(3));
    LoginLog::create(['user_id' => $user->id, 'pn_dicoba' => $user->pn, 'status' => LoginLog::STATUS_BERHASIL, 'ip_address' => '10.0.0.1']);
    $this->travelBack();
    // Percobaan GAGAL yang lebih baru gak boleh dianggap "login terakhir" --
    // cuma yang statusnya berhasil yang dihitung.
    LoginLog::create(['user_id' => $user->id, 'pn_dicoba' => $user->pn, 'status' => LoginLog::STATUS_GAGAL_KREDENSIAL, 'ip_address' => '10.0.0.2']);

    $response = $this->actingAs($admin)->get(route('users.index'));

    $response->assertOk();
    $loginTerakhir = $response->viewData('loginTerakhirPerUser');
    expect($loginTerakhir[$user->id])->not->toBeNull();
    expect(Carbon::parse($loginTerakhir[$user->id])->isSameDay(now()->subDays(3)))->toBeTrue();
});

test('user yang belum pernah login berhasil nampilin "Belum pernah login" di Kelola User', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($admin)->get(route('users.index'));

    $response->assertOk();
    $response->assertSee('Belum pernah login');
});

test('user baru bisa dibuat tanpa mengisi email sama sekali (email opsional, gak lagi wajib)', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $pekerja = Pekerja::factory()->create();

    $response = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Tanpa Email',
        'pn' => $pekerja->pn,
        'password' => 'password123',
        'role' => 'user',
        'uker_kode' => $uker->kode,
    ]);

    $response->assertRedirect(route('users.index'));
    $user = User::where('pn', $pekerja->pn)->first();
    expect($user)->not->toBeNull();
    expect($user->email)->toBeNull();
});

test('pn wajib diisi dan harus terdaftar di data pekerja saat menambah user', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();

    $kosong = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Tanpa PN',
        'password' => 'password123',
        'role' => 'user',
        'uker_kode' => $uker->kode,
    ]);
    $kosong->assertSessionHasErrors('pn');

    $tidakTerdaftar = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'PN Ngasal',
        'pn' => '99999999',
        'password' => 'password123',
        'role' => 'user',
        'uker_kode' => $uker->kode,
    ]);
    $tidakTerdaftar->assertSessionHasErrors('pn');

    expect(User::where('name', 'Tanpa PN')->exists())->toBeFalse();
    expect(User::where('name', 'PN Ngasal')->exists())->toBeFalse();
});

test('dropdown uker di form Tambah/Edit User cuma nampilin level KC ke atas, gak ada KCP/Unit', function () {
    $admin = User::factory()->admin()->create();
    $kanwil = Uker::factory()->create(['jenis' => 'KANWIL']);
    $kc = Uker::factory()->create(['jenis' => 'KC']);
    $kcp = Uker::factory()->create(['jenis' => 'KCP']);
    $unit = Uker::factory()->create(['jenis' => 'UNIT']);

    $create = $this->actingAs($admin)->get(route('users.create'));
    $create->assertOk();
    $kodeDiCreate = $create->viewData('ukerList')->pluck('kode');
    expect($kodeDiCreate)->toContain($kanwil->kode, $kc->kode);
    expect($kodeDiCreate)->not->toContain($kcp->kode, $unit->kode);

    $userDicek = User::factory()->forUker($kc->kode)->create();
    $edit = $this->actingAs($admin)->get(route('users.edit', $userDicek));
    $edit->assertOk();
    $kodeDiEdit = $edit->viewData('ukerList')->pluck('kode');
    expect($kodeDiEdit)->toContain($kanwil->kode, $kc->kode);
    expect($kodeDiEdit)->not->toContain($kcp->kode, $unit->kode);
});

test('edit user yang sudah ter-assign ke uker level KCP/Unit (data lama) tetap nampilin uker itu, gak ilang dari dropdown', function () {
    $admin = User::factory()->admin()->create();
    $kcp = Uker::factory()->create(['jenis' => 'KCP']);
    $userLama = User::factory()->forUker($kcp->kode)->create();

    $edit = $this->actingAs($admin)->get(route('users.edit', $userLama));

    $edit->assertOk();
    expect($edit->viewData('ukerList')->pluck('kode'))->toContain($kcp->kode);
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

test('admin bisa logout paksa semua sesi aktif user lain', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $lainnya = User::factory()->forUker($uker->kode)->create();

    DB::table('sessions')->insert([
        ['id' => 'sesi-1', 'user_id' => $lainnya->id, 'ip_address' => '10.0.0.1', 'user_agent' => 'A', 'payload' => 'x', 'last_activity' => now()->timestamp],
        ['id' => 'sesi-2', 'user_id' => $lainnya->id, 'ip_address' => '10.0.0.2', 'user_agent' => 'B', 'payload' => 'x', 'last_activity' => now()->timestamp],
    ]);

    $response = $this->actingAs($admin)->post(route('users.forceLogout', $lainnya));

    $response->assertRedirect();
    expect(DB::table('sessions')->where('user_id', $lainnya->id)->count())->toBe(0);
});

test('non-admin tidak bisa akses logout paksa', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $lainnya = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->post(route('users.forceLogout', $lainnya))->assertForbidden();
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

test('user biasa tidak bisa export kelola user', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('users.export.excel'))->assertForbidden();
    $this->actingAs($user)->get(route('users.export.pdf'))->assertForbidden();
});

test('admin bisa export kelola user Excel & PDF', function () {
    $admin = User::factory()->admin()->create();

    $excel = $this->actingAs($admin)->get(route('users.export.excel'));
    $excel->assertOk();
    $excel->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $pdf = $this->actingAs($admin)->get(route('users.export.pdf'));
    $pdf->assertOk();
    $pdf->assertHeader('content-type', 'application/pdf');
});
