<?php

use App\Models\Aset;
use App\Models\AsetEditRequest;
use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\KodeAset;
use App\Models\PermintaanPerangkat;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\ReminderInputAset;
use App\Notifications\ReminderPengisianHealthCheck;

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

test('kesehatan per kategori mencakup semua kategori checklist dari config, bukan kategori E', function () {
    $admin = User::factory()->admin()->create();
    $kategoriChecklist = array_keys(config('health_check_checklist'));

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $kategoriList = $response->viewData('kesehatanPerKategori')->pluck('kategori');
    expect($kategoriList)->toHaveCount(count($kategoriChecklist));
    expect($kategoriList->all())->toEqual($kategoriChecklist);
    expect($kategoriList)->not->toContain('E - Dokumentasi Visual');
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

// ===================== Cabang Terbaik Bulan Ini =====================

test('cabang terbaik bulan ini ranking 3 cabang tertinggi dari compliance bulan berjalan', function () {
    $admin = User::factory()->admin()->create();
    $ukerA = Uker::factory()->create(['uker_spv' => 'Cabang A']);
    $ukerB = Uker::factory()->create(['uker_spv' => 'Cabang B']);
    $ukerC = Uker::factory()->create(['uker_spv' => 'Cabang C']);
    $ukerD = Uker::factory()->create(['uker_spv' => 'Cabang D']);

    $formA = HealthCheckForm::factory()->create(['uker_kode' => $ukerA->kode, 'tanggal_pemeriksaan' => now()]);
    HealthCheckItem::factory()->count(10)->create(['health_check_form_id' => $formA->id, 'status' => 'OK']);

    $formB = HealthCheckForm::factory()->create(['uker_kode' => $ukerB->kode, 'tanggal_pemeriksaan' => now()]);
    HealthCheckItem::factory()->count(5)->create(['health_check_form_id' => $formB->id, 'status' => 'OK']);
    HealthCheckItem::factory()->count(5)->create(['health_check_form_id' => $formB->id, 'status' => 'Not OK']);

    $formC = HealthCheckForm::factory()->create(['uker_kode' => $ukerC->kode, 'tanggal_pemeriksaan' => now()]);
    HealthCheckItem::factory()->count(2)->create(['health_check_form_id' => $formC->id, 'status' => 'OK']);
    HealthCheckItem::factory()->count(8)->create(['health_check_form_id' => $formC->id, 'status' => 'Not OK']);

    // Cabang D cuma ada form BULAN LALU -- gak boleh ikut keitung di ranking bulan ini
    $formD = HealthCheckForm::factory()->create(['uker_kode' => $ukerD->kode, 'tanggal_pemeriksaan' => now()->subMonth()]);
    HealthCheckItem::factory()->count(10)->create(['health_check_form_id' => $formD->id, 'status' => 'OK']);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $ranking = $response->viewData('cabangTerbaikBulanIni');
    expect($ranking)->toHaveCount(3);
    expect($ranking[0]['cabang'])->toBe('Cabang A');
    expect($ranking[0]['persen'])->toBe(100.0);
    expect($ranking->pluck('cabang'))->not->toContain('Cabang D');
});

test('user biasa gak dapet data cabang terbaik bulan ini (khusus admin)', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    expect($response->viewData('cabangTerbaikBulanIni'))->toBeEmpty();
    $response->assertDontSee('Cabang Terbaik');
});

// ===================== Aktivitas Terbaru (dipersempit ke event actionable) =====================

test('aktivitas terbaru TIDAK nampilin aset baru ditambahkan atau form HC baru dibuat (rutin, gak actionable)', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'status_approval' => 'Draft']);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $teks = $response->viewData('aktivitasTerbaru')->pluck('teks')->implode(' ');
    expect($teks)->not->toContain('ditambahkan ke');
    expect($teks)->not->toContain('dibuat untuk');
});

test('aktivitas terbaru nampilin form HC yang disubmit untuk approval', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create(['nama' => 'KC Aktivitas Test']);
    HealthCheckForm::factory()->create([
        'uker_kode' => $uker->kode, 'periode' => 'Agustus 2026', 'status_approval' => 'Menunggu Approval',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $teks = $response->viewData('aktivitasTerbaru')->pluck('teks')->implode(' | ');
    expect($teks)->toContain('Agustus 2026', 'KC Aktivitas Test', 'disubmit untuk approval');
});

test('aktivitas terbaru nampilin item checklist yang berstatus Not OK', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create(['nama' => 'KC Kendala Test']);
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id, 'status' => 'Not OK', 'item_pemeriksaan' => 'AC ruang server mati',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $teks = $response->viewData('aktivitasTerbaru')->pluck('teks')->implode(' | ');
    expect($teks)->toContain('AC ruang server mati', 'KC Kendala Test', 'Not OK');
});

test('aktivitas terbaru nampilin permintaan perangkat yang baru diajukan', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create(['nama' => 'KC Permintaan Test']);
    PermintaanPerangkat::factory()->create(['uker_kode' => $uker->kode, 'no_nota_dinas' => 'ND-AKTIVITAS-001']);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $teks = $response->viewData('aktivitasTerbaru')->pluck('teks')->implode(' | ');
    expect($teks)->toContain('ND-AKTIVITAS-001', 'KC Permintaan Test');
});

test('aktivitas terbaru nampilin permintaan edit aset yang baru diajukan', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'no_asset' => 'AKTIVITAS-TEST-001']);
    AsetEditRequest::create(['aset_id' => $aset->id, 'requested_by' => $user->id, 'status' => 'Menunggu']);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $teks = $response->viewData('aktivitasTerbaru')->pluck('teks')->implode(' | ');
    expect($teks)->toContain('AKTIVITAS-TEST-001', 'diajukan');
});

test('aktivitas terbaru Permintaan Perangkat di-scope exact-match uker sendiri buat non-admin (bukan subtree)', function () {
    $cabangA = Uker::factory()->create();
    $anakCabangA = Uker::factory()->create(['kode_spv' => $cabangA->kode]);
    $userA = User::factory()->forUker($cabangA->kode)->create();
    PermintaanPerangkat::factory()->create(['uker_kode' => $cabangA->kode, 'no_nota_dinas' => 'ND-MILIK-SENDIRI']);
    PermintaanPerangkat::factory()->create(['uker_kode' => $anakCabangA->kode, 'no_nota_dinas' => 'ND-MILIK-ANAK']);

    $response = $this->actingAs($userA)->get(route('dashboard'));

    $teks = $response->viewData('aktivitasTerbaru')->pluck('teks')->implode(' | ');
    expect($teks)->toContain('ND-MILIK-SENDIRI');
    expect($teks)->not->toContain('ND-MILIK-ANAK');
});

test('itemDetail nampilin item checklist sesuai kategori & status yang diminta, dari form terbaru tiap uker', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $formLama = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->subDays(10)]);
    $formBaru = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()]);

    HealthCheckItem::factory()->create([
        'health_check_form_id' => $formLama->id, 'kategori' => 'A - Ruang Server/Jaringan',
        'status' => 'OK', 'item_pemeriksaan' => 'Item form lama',
    ]);
    HealthCheckItem::factory()->create([
        'health_check_form_id' => $formBaru->id, 'kategori' => 'A - Ruang Server/Jaringan',
        'status' => 'OK', 'item_pemeriksaan' => 'Item form baru',
    ]);
    HealthCheckItem::factory()->create([
        'health_check_form_id' => $formBaru->id, 'kategori' => 'B - CCTV & Storage',
        'status' => 'OK', 'item_pemeriksaan' => 'Item kategori beda',
    ]);

    $response = $this->actingAs($admin)->getJson(route('dashboard.itemDetail', [
        'kategori' => 'A - Ruang Server/Jaringan', 'status' => 'OK',
    ]));

    $response->assertOk();
    $items = collect($response->json('items'));
    expect($items)->toHaveCount(1);
    expect($items->first()['item_pemeriksaan'])->toBe('Item form baru');
});

test('itemDetail user biasa cuma lihat item dari uker sendiri + turunannya', function () {
    $cabangA = Uker::factory()->create();
    $anakCabangA = Uker::factory()->create(['kode_spv' => $cabangA->kode]);
    $cabangB = Uker::factory()->create();
    $userA = User::factory()->forUker($cabangA->kode)->create();

    $formAnak = HealthCheckForm::factory()->create(['uker_kode' => $anakCabangA->kode]);
    $formB = HealthCheckForm::factory()->create(['uker_kode' => $cabangB->kode]);

    HealthCheckItem::factory()->create([
        'health_check_form_id' => $formAnak->id, 'kategori' => 'A - Ruang Server/Jaringan',
        'status' => 'Not OK', 'item_pemeriksaan' => 'Item milik subtree A',
    ]);
    HealthCheckItem::factory()->create([
        'health_check_form_id' => $formB->id, 'kategori' => 'A - Ruang Server/Jaringan',
        'status' => 'Not OK', 'item_pemeriksaan' => 'Item milik cabang B',
    ]);

    $response = $this->actingAs($userA)->getJson(route('dashboard.itemDetail', [
        'kategori' => 'A - Ruang Server/Jaringan', 'status' => 'Not OK',
    ]));

    $response->assertOk();
    $teks = collect($response->json('items'))->pluck('item_pemeriksaan');
    expect($teks)->toContain('Item milik subtree A');
    expect($teks)->not->toContain('Item milik cabang B');
});

test('itemDetail nolak status yang bukan salah satu dari OK/Not OK/N-A/Belum Diperiksa', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->getJson(route('dashboard.itemDetail', [
        'kategori' => 'A - Ruang Server/Jaringan', 'status' => 'Ngasal',
    ]));

    $response->assertStatus(422);
});

test('admin bisa kirim pengingat pengisian HC ke uker manapun', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $userUker = User::factory()->forUker($uker->kode)->create();

    $response = $this->actingAs($admin)->post(route('dashboard.kirimPengingatHc', $uker));

    $response->assertRedirect();
    $response->assertSessionHas('status');
    expect($userUker->notifications()->where('type', ReminderPengisianHealthCheck::class)->exists())->toBeTrue();
});

test('admin bisa kirim pengingat input aset ke uker manapun', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $userUker = User::factory()->forUker($uker->kode)->create();

    $response = $this->actingAs($admin)->post(route('dashboard.kirimPengingatAset', $uker));

    $response->assertRedirect();
    expect($userUker->notifications()->where('type', ReminderInputAset::class)->exists())->toBeTrue();
});

test('user biasa cuma bisa kirim pengingat ke uker di subtree sendiri, bukan uker lain', function () {
    $cabangA = Uker::factory()->create();
    $anakCabangA = Uker::factory()->create(['kode_spv' => $cabangA->kode]);
    $cabangB = Uker::factory()->create();
    $userA = User::factory()->forUker($cabangA->kode)->create();
    User::factory()->forUker($anakCabangA->kode)->create();
    User::factory()->forUker($cabangB->kode)->create();

    $bolehSubtree = $this->actingAs($userA)->post(route('dashboard.kirimPengingatHc', $anakCabangA));
    $bolehSubtree->assertRedirect();

    $tolakLuarSubtree = $this->actingAs($userA)->post(route('dashboard.kirimPengingatHc', $cabangB));
    $tolakLuarSubtree->assertForbidden();
});

test('kirim pengingat ditolak kalau uker belum punya user aktif', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();

    $response = $this->actingAs($admin)->post(route('dashboard.kirimPengingatHc', $uker));

    $response->assertStatus(422);
});

test('widget Perlu Tindakan Anda misahin angka Kendala Melewati SLA dari Belum Ditindaklanjuti', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);

    HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id, 'status' => 'Not OK',
        'status_tindak_lanjut' => 'Sedang Diproses', 'mulai_diproses_at' => now()->subDays(10),
    ]);
    HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id, 'status' => 'Not OK',
        'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $aksiPerlu = $response->viewData('aksiPerlu');
    $labels = $aksiPerlu->pluck('label');
    expect($labels)->toContain('Kendala belum ditindaklanjuti', 'Kendala sedang diproses tapi melewati SLA');

    $slaEntry = $aksiPerlu->firstWhere('label', 'Kendala sedang diproses tapi melewati SLA');
    expect($slaEntry['jumlah'])->toBe(1);
    expect($slaEntry['href'])->toBe(route('monitoring.index', ['melewati_sla' => 1]));
});

test('widget Perlu Tindakan Anda nampilin aset yang belum pernah dicek ulang lebih dari 6 bulan', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();

    // Punya kondisiLog, tapi udah lebih dari 180 hari -- HARUS kehitung
    $asetLama = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $asetLama->kondisiLogs()->create(['kondisi_baru' => 'NORMAL', 'changed_by' => $admin->id])
        ->forceFill(['created_at' => now()->subDays(200)])->save();

    // Baru dicek minggu lalu -- HARUS gak kehitung
    $asetBaru = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $asetBaru->kondisiLogs()->create(['kondisi_baru' => 'NORMAL', 'changed_by' => $admin->id])
        ->forceFill(['created_at' => now()->subDays(5)])->save();

    // Gak punya kondisiLog sama sekali (khas hasil bulk upload lama) -- HARUS kehitung
    Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $entry = $response->viewData('aksiPerlu')->firstWhere('label', 'Aset belum pernah dicek ulang kondisinya (>6 bulan)');
    expect($entry)->not->toBeNull();
    expect($entry['jumlah'])->toBe(2);
    expect($entry['href'])->toBe(route('aset.index', ['perlu_dicek_ulang' => 1]));
});

test('dashboard nunjukin jumlah aset & form health check yang baru ditambahkan 7 hari terakhir', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();

    Aset::factory()->create(['uker_kode' => $uker->kode, 'created_at' => now()->subDays(2)]);
    Aset::factory()->create(['uker_kode' => $uker->kode, 'created_at' => now()->subDays(20)]);
    HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'created_at' => now()->subDays(1)]);
    HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'created_at' => now()->subDays(30)]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertViewHas('asetBaruMingguIni', 1);
    $response->assertViewHas('formBaruMingguIni', 1);
});
