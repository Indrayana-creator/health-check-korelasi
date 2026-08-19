<?php

use App\Models\PermintaanPerangkat;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\PermintaanPerangkatDiajukan;
use App\Notifications\PermintaanPerangkatStatusDiupdate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

test('guest tidak bisa akses permintaan perangkat', function () {
    $this->get(route('permintaan-perangkat.index'))->assertRedirect(route('login'));
});

test('user cabang cuma melihat permintaan uker sendiri, bukan uker lain (exact match, bukan subtree)', function () {
    $cabangA = Uker::factory()->create();
    $anakCabangA = Uker::factory()->create(['kode_spv' => $cabangA->kode]);
    $cabangB = Uker::factory()->create();
    $userA = User::factory()->forUker($cabangA->kode)->create();

    $milikA = PermintaanPerangkat::factory()->create(['uker_kode' => $cabangA->kode, 'no_nota_dinas' => 'ND-A']);
    // Meskipun anak cabang A ada di subtree-nya, modul ini EXACT MATCH aja
    // (bukan hierarki kayak Aset/HealthCheck) -- jadi gak ikut kelihatan.
    PermintaanPerangkat::factory()->create(['uker_kode' => $anakCabangA->kode, 'no_nota_dinas' => 'ND-ANAK']);
    PermintaanPerangkat::factory()->create(['uker_kode' => $cabangB->kode, 'no_nota_dinas' => 'ND-B']);

    $response = $this->actingAs($userA)->get(route('permintaan-perangkat.index'));

    $response->assertOk();
    $daftar = $response->viewData('permintaanList')->pluck('no_nota_dinas');
    expect($daftar)->toContain('ND-A');
    expect($daftar)->not->toContain('ND-ANAK', 'ND-B');
});

test('admin melihat SEMUA permintaan dari seluruh uker, bisa difilter uker & status', function () {
    $admin = User::factory()->admin()->create();
    $cabangA = Uker::factory()->create();
    $cabangB = Uker::factory()->create();
    PermintaanPerangkat::factory()->create(['uker_kode' => $cabangA->kode, 'status' => 'Pending IT', 'no_nota_dinas' => 'ND-A']);
    PermintaanPerangkat::factory()->create(['uker_kode' => $cabangB->kode, 'status' => 'Done Terkirim', 'no_nota_dinas' => 'ND-B']);

    $responseSemua = $this->actingAs($admin)->get(route('permintaan-perangkat.index'));
    expect($responseSemua->viewData('permintaanList'))->toHaveCount(2);

    $responseUker = $this->actingAs($admin)->get(route('permintaan-perangkat.index', ['uker_kode' => $cabangA->kode]));
    expect($responseUker->viewData('permintaanList')->pluck('no_nota_dinas')->all())->toBe(['ND-A']);

    $responseStatus = $this->actingAs($admin)->get(route('permintaan-perangkat.index', ['status' => 'Done Terkirim']));
    expect($responseStatus->viewData('permintaanList')->pluck('no_nota_dinas')->all())->toBe(['ND-B']);
});

test('user cabang bisa mengajukan permintaan baru, uker_kode otomatis dari akun sendiri (tidak bisa dipilih manual)', function () {
    Notification::fake();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $response = $this->actingAs($user)->post(route('permintaan-perangkat.store'), [
        'no_nota_dinas' => 'ND-001/VIII/2026',
        'fungsi_requester' => 'RSF',
        'jumlah' => 3,
        'keterangan' => 'Butuh 3 unit PC baru buat teller',
        // uker_kode SENGAJA gak dikirim -- harus otomatis dari akun login,
        // bukan dari input form.
    ]);

    $response->assertRedirect(route('permintaan-perangkat.index'));
    $permintaan = PermintaanPerangkat::where('no_nota_dinas', 'ND-001/VIII/2026')->first();
    expect($permintaan)->not->toBeNull();
    expect($permintaan->uker_kode)->toBe($uker->kode);
    expect($permintaan->requested_by)->toBe($user->id);
    expect($permintaan->status)->toBe('Pending IT');
    expect($permintaan->tanggal_request->toDateString())->toBe(now()->toDateString());
});

test('admin TIDAK bisa mengajukan permintaan perangkat, cuma cabang', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('permintaan-perangkat.store'), [
        'no_nota_dinas' => 'ND-002', 'fungsi_requester' => 'RSF', 'jumlah' => 1, 'keterangan' => 'Test',
    ]);

    $response->assertForbidden();
});

test('pengajuan wajib isi semua field, jumlah minimal 1', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $response = $this->actingAs($user)->post(route('permintaan-perangkat.store'), [
        'no_nota_dinas' => '', 'fungsi_requester' => '', 'jumlah' => 0, 'keterangan' => '',
    ]);

    $response->assertSessionHasErrors(['no_nota_dinas', 'fungsi_requester', 'jumlah', 'keterangan']);
});

test('admin bisa update status ke status APAPUN secara bebas, tidak harus urut IT->ESO->LGA->Done', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $permintaan = PermintaanPerangkat::factory()->create(['uker_kode' => $uker->kode, 'status' => 'Pending IT']);

    // Loncat langsung dari "Pending IT" ke "Done Terkirim", skip ESO & LGA.
    $response = $this->actingAs($admin)->post(route('permintaan-perangkat.updateStatus', $permintaan), [
        'status' => 'Done Terkirim',
        'catatan_admin' => 'Sudah dikirim via kurir internal',
    ]);

    $response->assertRedirect();
    $permintaan->refresh();
    expect($permintaan->status)->toBe('Done Terkirim');
    expect($permintaan->catatan_admin)->toBe('Sudah dikirim via kurir internal');

    // Lalu balik lagi ke "Pending LGA" -- bebas, gak dipaksa maju terus.
    $this->actingAs($admin)->post(route('permintaan-perangkat.updateStatus', $permintaan), ['status' => 'Pending LGA']);
    expect($permintaan->fresh()->status)->toBe('Pending LGA');
});

test('user cabang TIDAK BISA update status maupun hapus permintaan', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $permintaan = PermintaanPerangkat::factory()->create(['uker_kode' => $uker->kode, 'status' => 'Pending IT']);

    $response = $this->actingAs($user)->post(route('permintaan-perangkat.updateStatus', $permintaan), [
        'status' => 'Done Terkirim',
    ]);

    $response->assertForbidden();
    expect($permintaan->fresh()->status)->toBe('Pending IT');
});

test('semua admin dinotifikasi waktu cabang mengajukan permintaan baru', function () {
    Notification::fake();
    $admin1 = User::factory()->admin()->create();
    $admin2 = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->post(route('permintaan-perangkat.store'), [
        'no_nota_dinas' => 'ND-003', 'fungsi_requester' => 'MRR', 'jumlah' => 2, 'keterangan' => 'Perbaikan printer',
    ]);

    Notification::assertSentTo($admin1, PermintaanPerangkatDiajukan::class);
    Notification::assertSentTo($admin2, PermintaanPerangkatDiajukan::class);
});

test('user yang mengajukan dinotifikasi balik waktu admin update status', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $permintaan = PermintaanPerangkat::factory()->create(['uker_kode' => $uker->kode, 'requested_by' => $user->id]);

    $this->actingAs($admin)->post(route('permintaan-perangkat.updateStatus', $permintaan), [
        'status' => 'Pending ESO',
    ]);

    Notification::assertSentTo($user, PermintaanPerangkatStatusDiupdate::class);
});

// ===================== Export =====================

test('admin bisa export permintaan perangkat ke excel', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    PermintaanPerangkat::factory()->create(['uker_kode' => $uker->kode]);

    $response = $this->actingAs($admin)->get(route('permintaan-perangkat.export.excel'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('admin bisa export permintaan perangkat ke pdf', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    PermintaanPerangkat::factory()->create(['uker_kode' => $uker->kode]);

    $response = $this->actingAs($admin)->get(route('permintaan-perangkat.export.pdf'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

test('export permintaan perangkat tetap ikut filter status yang aktif', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    PermintaanPerangkat::factory()->create(['uker_kode' => $uker->kode, 'status' => 'Pending IT']);
    PermintaanPerangkat::factory()->create(['uker_kode' => $uker->kode, 'status' => 'Done Terkirim']);

    $response = $this->actingAs($admin)->get(route('permintaan-perangkat.export.excel', ['status' => 'Done Terkirim']));

    $response->assertOk();
});

test('user cabang bisa export tapi cuma dapet punya uker sendiri (exact match)', function () {
    $cabangA = Uker::factory()->create();
    $cabangB = Uker::factory()->create();
    $userA = User::factory()->forUker($cabangA->kode)->create();
    PermintaanPerangkat::factory()->create(['uker_kode' => $cabangA->kode, 'no_nota_dinas' => 'ND-A']);
    PermintaanPerangkat::factory()->create(['uker_kode' => $cabangB->kode, 'no_nota_dinas' => 'ND-B']);

    $response = $this->actingAs($userA)->get(route('permintaan-perangkat.export.excel'));

    $response->assertOk();
});

// ===================== Rekap Mingguan =====================

test('user biasa tidak bisa akses rekap permintaan perangkat', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('rekap.permintaanPerangkat'))->assertForbidden();
});

test('rekap mingguan cuma menghitung permintaan dalam rentang Senin-Jumat minggu yang dipilih', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();

    $seninMingguIni = now()->startOfWeek(Carbon::MONDAY);
    PermintaanPerangkat::factory()->create([
        'uker_kode' => $uker->kode, 'no_nota_dinas' => 'ND-MINGGU-INI',
        'tanggal_request' => $seninMingguIni->copy()->addDays(2), // Rabu minggu ini
    ]);
    PermintaanPerangkat::factory()->create([
        'uker_kode' => $uker->kode, 'no_nota_dinas' => 'ND-MINGGU-LALU',
        'tanggal_request' => $seninMingguIni->copy()->subWeek(), // Senin minggu lalu
    ]);

    $response = $this->actingAs($admin)->get(route('rekap.permintaanPerangkat'));

    $response->assertOk();
    $daftar = $response->viewData('permintaanList')->pluck('no_nota_dinas');
    expect($daftar)->toContain('ND-MINGGU-INI');
    expect($daftar)->not->toContain('ND-MINGGU-LALU');
    expect($response->viewData('totalMinggu'))->toBe(1);
});

test('rekap mingguan breakdown menghitung jumlah per status dengan benar', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $seninMingguIni = now()->startOfWeek(Carbon::MONDAY);

    PermintaanPerangkat::factory()->create(['uker_kode' => $uker->kode, 'status' => 'Pending IT', 'tanggal_request' => $seninMingguIni]);
    PermintaanPerangkat::factory()->create(['uker_kode' => $uker->kode, 'status' => 'Pending IT', 'tanggal_request' => $seninMingguIni]);
    PermintaanPerangkat::factory()->create(['uker_kode' => $uker->kode, 'status' => 'Done Terkirim', 'tanggal_request' => $seninMingguIni]);

    $response = $this->actingAs($admin)->get(route('rekap.permintaanPerangkat'));

    $breakdown = $response->viewData('breakdownStatus');
    expect($breakdown['Pending IT'])->toBe(2);
    expect($breakdown['Done Terkirim'])->toBe(1);
    expect($breakdown['Pending ESO'])->toBe(0);
    expect($breakdown['Pending LGA'])->toBe(0);
});

test('rekap mingguan bisa navigasi ke minggu lain lewat query string ?minggu=', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $seninMingguLalu = now()->startOfWeek(Carbon::MONDAY)->subWeek();

    PermintaanPerangkat::factory()->create([
        'uker_kode' => $uker->kode, 'no_nota_dinas' => 'ND-MINGGU-LALU', 'tanggal_request' => $seninMingguLalu,
    ]);

    $response = $this->actingAs($admin)->get(route('rekap.permintaanPerangkat', ['minggu' => $seninMingguLalu->toDateString()]));

    $response->assertOk();
    expect($response->viewData('permintaanList')->pluck('no_nota_dinas'))->toContain('ND-MINGGU-LALU');
});

test('user biasa tidak bisa export rekap permintaan perangkat', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('rekap.permintaanPerangkat.export.excel'))->assertForbidden();
    $this->actingAs($user)->get(route('rekap.permintaanPerangkat.export.pdf'))->assertForbidden();
});

test('export rekap permintaan perangkat Excel & PDF berhasil, ngikutin minggu yang lagi dinavigasi', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $seninMingguLalu = now()->startOfWeek(Carbon::MONDAY)->subWeek();
    PermintaanPerangkat::factory()->create([
        'uker_kode' => $uker->kode, 'no_nota_dinas' => 'ND-EXPORT-TEST', 'tanggal_request' => $seninMingguLalu,
    ]);

    $this->actingAs($admin)->get(route('rekap.permintaanPerangkat.export.excel', ['minggu' => $seninMingguLalu->toDateString()]))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($admin)->get(route('rekap.permintaanPerangkat.export.pdf', ['minggu' => $seninMingguLalu->toDateString()]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('admin bisa update status banyak permintaan sekaligus (bulk)', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $requester = User::factory()->forUker($uker->kode)->create();
    $p1 = PermintaanPerangkat::factory()->create(['uker_kode' => $uker->kode, 'requested_by' => $requester->id, 'status' => 'Pending IT']);
    $p2 = PermintaanPerangkat::factory()->create(['uker_kode' => $uker->kode, 'requested_by' => $requester->id, 'status' => 'Pending IT']);
    $tidakIkut = PermintaanPerangkat::factory()->create(['uker_kode' => $uker->kode, 'requested_by' => $requester->id, 'status' => 'Pending IT']);

    $response = $this->actingAs($admin)->post(route('permintaan-perangkat.bulkUpdateStatus'), [
        'ids' => [$p1->id, $p2->id],
        'status' => 'Pending ESO',
    ]);

    $response->assertRedirect();
    expect($p1->fresh()->status)->toBe('Pending ESO');
    expect($p2->fresh()->status)->toBe('Pending ESO');
    expect($tidakIkut->fresh()->status)->toBe('Pending IT');
    Notification::assertSentToTimes($requester, PermintaanPerangkatStatusDiupdate::class, 2);
});

test('user biasa tidak bisa bulk update status permintaan perangkat', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $p1 = PermintaanPerangkat::factory()->create(['uker_kode' => $uker->kode, 'status' => 'Pending IT']);

    $response = $this->actingAs($user)->post(route('permintaan-perangkat.bulkUpdateStatus'), [
        'ids' => [$p1->id],
        'status' => 'Pending ESO',
    ]);

    $response->assertForbidden();
    expect($p1->fresh()->status)->toBe('Pending IT');
});

test('bulk update status nolak kalau ids kosong atau status gak valid', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $p1 = PermintaanPerangkat::factory()->create(['uker_kode' => $uker->kode]);

    $this->actingAs($admin)->post(route('permintaan-perangkat.bulkUpdateStatus'), [
        'ids' => [],
        'status' => 'Pending ESO',
    ])->assertSessionHasErrors('ids');

    $this->actingAs($admin)->post(route('permintaan-perangkat.bulkUpdateStatus'), [
        'ids' => [$p1->id],
        'status' => 'Ngasal',
    ])->assertSessionHasErrors('status');
});
