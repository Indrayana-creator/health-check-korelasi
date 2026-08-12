<?php

use App\Models\Aset;
use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\KodeAset;
use App\Models\Uker;
use App\Models\User;

test('dashboard admin menghitung total kendala aktif dari item Not OK yang belum selesai ditindaklanjuti', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);

    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti']);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Sedang Diproses']);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Selesai Diperbaiki']);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'OK']);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertViewHas('totalKendalaAktif', 2);
});

test('dashboard user biasa tidak menghitung total kendala aktif', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertViewHas('totalKendalaAktif', null);
});

test('kesehatan per kategori cuma ngitung dari form TERBARU per uker, form lama diabaikan', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();

    $formLama = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->subWeeks(2)]);
    HealthCheckItem::factory()->count(4)->create(['health_check_form_id' => $formLama->id, 'kategori' => 'A - Ruang Server/Jaringan', 'status' => 'Not OK']);

    $formBaru = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()]);
    HealthCheckItem::factory()->count(3)->create(['health_check_form_id' => $formBaru->id, 'kategori' => 'A - Ruang Server/Jaringan', 'status' => 'OK']);
    HealthCheckItem::factory()->count(1)->create(['health_check_form_id' => $formBaru->id, 'kategori' => 'A - Ruang Server/Jaringan', 'status' => 'Not OK']);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $kategoriA = $response->viewData('kesehatanPerKategori')->firstWhere('kategori', 'A - Ruang Server/Jaringan');
    // Cuma dari form BARU (3 OK, 1 Not OK) -- form lama (4 Not OK) diabaikan total.
    expect($kategoriA['breakdown']['OK'])->toBe(3);
    expect($kategoriA['breakdown']['Not OK'])->toBe(1);
    expect($kategoriA['persen'])->toBe(75.0);
    expect($kategoriA['label'])->toBe('PERLU PERHATIAN'); // 75% < 80%
});

test('kesehatan per kategori di-scope RBAC: user cuma lihat uker sendiri + turunan, admin lihat semua', function () {
    $admin = User::factory()->admin()->create();
    $ukerSendiri = Uker::factory()->create();
    $ukerAnak = Uker::factory()->create(['kode_spv' => $ukerSendiri->kode]);
    $ukerLain = Uker::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();

    $formSendiri = HealthCheckForm::factory()->create(['uker_kode' => $ukerSendiri->kode, 'tanggal_pemeriksaan' => now()]);
    HealthCheckItem::factory()->count(2)->create(['health_check_form_id' => $formSendiri->id, 'kategori' => 'B - CCTV & Storage', 'status' => 'OK']);

    $formAnak = HealthCheckForm::factory()->create(['uker_kode' => $ukerAnak->kode, 'tanggal_pemeriksaan' => now()]);
    HealthCheckItem::factory()->count(2)->create(['health_check_form_id' => $formAnak->id, 'kategori' => 'B - CCTV & Storage', 'status' => 'OK']);

    $formLain = HealthCheckForm::factory()->create(['uker_kode' => $ukerLain->kode, 'tanggal_pemeriksaan' => now()]);
    HealthCheckItem::factory()->count(2)->create(['health_check_form_id' => $formLain->id, 'kategori' => 'B - CCTV & Storage', 'status' => 'Not OK']);

    $responseUser = $this->actingAs($user)->get(route('dashboard'));
    $kategoriBUser = $responseUser->viewData('kesehatanPerKategori')->firstWhere('kategori', 'B - CCTV & Storage');
    // User cuma lihat uker sendiri + anak (4 OK total), uker lain (Not OK) gak ikut.
    expect($kategoriBUser['breakdown']['OK'])->toBe(4);
    expect($kategoriBUser['breakdown']['Not OK'])->toBe(0);

    $responseAdmin = $this->actingAs($admin)->get(route('dashboard'));
    $kategoriBAdmin = $responseAdmin->viewData('kesehatanPerKategori')->firstWhere('kategori', 'B - CCTV & Storage');
    // Admin lihat semua: 4 OK + 2 Not OK.
    expect($kategoriBAdmin['breakdown']['OK'])->toBe(4);
    expect($kategoriBAdmin['breakdown']['Not OK'])->toBe(2);
});

test('kesehatan per kategori mencakup 4 kategori A-D, bukan kategori E', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $kategoriList = $response->viewData('kesehatanPerKategori')->pluck('kategori');
    expect($kategoriList)->toHaveCount(4);
    expect($kategoriList)->toContain(
        'A - Ruang Server/Jaringan', 'B - CCTV & Storage', 'C - Jaringan', 'D - Power System (UPS)'
    );
});

test('dokumentasi visual (kategori E) dihitung terpisah dan gak masuk compliance kategori manapun', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();

    $formLengkap = HealthCheckForm::factory()->create([
        'uker_kode' => $uker->kode,
        'tanggal_pemeriksaan' => now(),
        'foto_ruang_server_url' => 'https://contoh.com/a.jpg',
        'foto_storage_cctv_url' => 'https://contoh.com/b.jpg',
        'foto_panel_ups_url' => 'https://contoh.com/c.jpg',
    ]);
    HealthCheckItem::factory()->count(2)->create(['health_check_form_id' => $formLengkap->id, 'kategori' => 'A - Ruang Server/Jaringan', 'status' => 'OK']);

    $ukerLain = Uker::factory()->create();
    HealthCheckForm::factory()->create(['uker_kode' => $ukerLain->kode, 'tanggal_pemeriksaan' => now()]); // belum ada foto sama sekali

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    expect($response->viewData('totalFormTerbaru'))->toBe(2);
    expect($response->viewData('formLengkapDokumentasi'))->toBe(1);

    // Kategori A tetap 100% OK -- gak kepengaruh sama sekali oleh field dokumentasi visual.
    $kategoriA = $response->viewData('kesehatanPerKategori')->firstWhere('kategori', 'A - Ruang Server/Jaringan');
    expect($kategoriA['persen'])->toBe(100.0);
});

// ===================== "Belum Isi HC" & "Belum Ada Aset" =====================

test('admin melihat "Belum Isi HC" dan "Belum Ada Aset" dari SEMUA uker, gak di-scope', function () {
    $admin = User::factory()->admin()->create();
    $ukerSudahIsi = Uker::factory()->create(['nama' => 'Sudah Isi HC']);
    $ukerBelumIsi = Uker::factory()->create(['nama' => 'Belum Isi HC']);
    HealthCheckForm::factory()->create(['uker_kode' => $ukerSudahIsi->kode]);

    $kodeAset = KodeAset::factory()->create();
    $ukerAdaAset = Uker::factory()->create(['nama' => 'Ada Aset']);
    $ukerBelumAdaAset = Uker::factory()->create(['nama' => 'Belum Ada Aset']);
    Aset::factory()->create(['uker_kode' => $ukerAdaAset->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    expect($response->viewData('ukerBelumMengisi')->pluck('kode'))->toContain($ukerBelumIsi->kode);
    expect($response->viewData('ukerBelumMengisi')->pluck('kode'))->not->toContain($ukerSudahIsi->kode);
    expect($response->viewData('ukerBelumAdaAset')->pluck('kode'))->toContain($ukerBelumAdaAset->kode);
    expect($response->viewData('ukerBelumAdaAset')->pluck('kode'))->not->toContain($ukerAdaAset->kode);
});

test('user Cabang A melihat "Belum Isi HC" & "Belum Ada Aset" cuma dari subtree sendiri, bukan seluruh sistem', function () {
    $cabangA = Uker::factory()->create();
    $kcpA1 = Uker::factory()->create(['nama' => 'KCP A1 Belum Isi', 'kode_spv' => $cabangA->kode]);
    $cabangB = Uker::factory()->create(['nama' => 'Cabang B Belum Isi']); // di luar subtree Cabang A
    $user = User::factory()->forUker($cabangA->kode)->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $kodeBelumIsi = $response->viewData('ukerBelumMengisi')->pluck('kode');
    expect($kodeBelumIsi)->toContain($cabangA->kode, $kcpA1->kode);
    expect($kodeBelumIsi)->not->toContain($cabangB->kode);

    $kodeBelumAset = $response->viewData('ukerBelumAdaAset')->pluck('kode');
    expect($kodeBelumAset)->toContain($cabangA->kode, $kcpA1->kode);
    expect($kodeBelumAset)->not->toContain($cabangB->kode);
});

test('user Cabang A gak lagi masuk "Belum Isi HC" kalau salah satu turunannya (KCP) sudah isi form', function () {
    $cabangA = Uker::factory()->create();
    $kcpA1 = Uker::factory()->create(['kode_spv' => $cabangA->kode]);
    $user = User::factory()->forUker($cabangA->kode)->create();
    HealthCheckForm::factory()->create(['uker_kode' => $kcpA1->kode]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $kodeBelumIsi = $response->viewData('ukerBelumMengisi')->pluck('kode');
    expect($kodeBelumIsi)->not->toContain($kcpA1->kode);
    expect($kodeBelumIsi)->toContain($cabangA->kode); // Cabang A sendiri masih belum isi form-nya sendiri
});

test('tab Ranking Terendah TIDAK muncul buat non-admin, panel Belum Isi HC/Belum Ada Aset tetap muncul', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    expect($response->viewData('rankingCabang'))->toBeEmpty();
    $response->assertSee('Belum Isi HC');
    $response->assertSee('Belum Ada Aset');
    $response->assertDontSee('Ranking Terendah');
});
