<?php

use App\Models\Aset;
use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\KodeAset;
use App\Models\Uker;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

// bangunTreeUker() (dipakai di /uker-tree & dashboard) buat ADMIN selalu
// mulai dari Kanwil kode 146 sebagai root -- jadi tiap test yang butuh tree
// admin beneran harus bikin Uker dengan kode itu persis.
function buatStrukturUkerUjiCoba(): array
{
    $kanwil = Uker::factory()->create(['kode' => 146, 'nama' => 'Kanwil Uji', 'jenis' => 'KANWIL', 'kode_spv' => 146]);
    $area = Uker::factory()->create(['kode' => 200, 'nama' => 'Area Uji', 'jenis' => 'AREA', 'kode_spv' => 146]);
    $kc = Uker::factory()->create(['kode' => 300, 'nama' => 'KC Uji', 'jenis' => 'KC', 'kode_spv' => 200]);

    return [$kanwil, $area, $kc];
}

// Struktur 2 cabang terpisah (gak nyambung ke Kanwil 146 di atas) buat nguji
// batasan akses role "user": Cabang A + turunannya (KCP A1) vs Cabang B +
// turunannya (KCP B1), keduanya sama sekali gak terhubung satu sama lain.
function buatStrukturCabangUjiCoba(): array
{
    $cabangA = Uker::factory()->create(['nama' => 'Cabang A', 'jenis' => 'KC']);
    $kcpA1 = Uker::factory()->create(['nama' => 'KCP A1', 'jenis' => 'KCP', 'kode_spv' => $cabangA->kode]);
    $cabangB = Uker::factory()->create(['nama' => 'Cabang B', 'jenis' => 'KC']);
    $kcpB1 = Uker::factory()->create(['nama' => 'KCP B1', 'jenis' => 'KCP', 'kode_spv' => $cabangB->kode]);

    return compact('cabangA', 'kcpA1', 'cabangB', 'kcpB1');
}

test('guest tidak bisa akses struktur organisasi', function () {
    $this->get(route('uker-tree.index'))->assertRedirect(route('login'));
});

test('user biasa BISA akses halaman struktur organisasi, root tree-nya uker sendiri (bukan Kanwil)', function () {
    $s = buatStrukturCabangUjiCoba();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    $response = $this->actingAs($user)->get(route('uker-tree.index'));

    $response->assertOk();
    $tree = $response->viewData('tree');
    expect($tree['kode'])->toBe($s['cabangA']->kode);
    expect($tree['nama'])->toBe('Cabang A');
    $anak = $tree['anak']->pluck('kode');
    expect($anak)->toContain($s['kcpA1']->kode);
    expect($anak)->not->toContain($s['cabangB']->kode, $s['kcpB1']->kode);
});

test('halaman struktur organisasi tetap tampil kalau data uker kanwil (146) belum ada', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('uker-tree.index'));

    $response->assertOk();
    expect($response->viewData('tree'))->toBeNull();
});

test('tree menghitung total aset dan rata-rata compliance berjenjang dari cucu ke root', function () {
    $admin = User::factory()->admin()->create();
    [$kanwil, $area, $kc] = buatStrukturUkerUjiCoba();

    $kodeAset = KodeAset::factory()->create();
    Aset::factory()->count(2)->create(['uker_kode' => $kc->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $form = HealthCheckForm::factory()->create(['uker_kode' => $kc->kode]);
    HealthCheckItem::factory()->count(3)->create(['health_check_form_id' => $form->id, 'status' => 'OK']);
    HealthCheckItem::factory()->count(1)->create(['health_check_form_id' => $form->id, 'status' => 'Not OK']);

    $response = $this->actingAs($admin)->get(route('uker-tree.index'));
    $response->assertOk();

    $tree = $response->viewData('tree');
    expect($tree['kode'])->toBe(146);
    expect($tree['total_aset'])->toBe(2);
    expect($tree['jumlah_unit_bawah'])->toBe(2); // area + kc
    expect($tree['rata_compliance'])->toBe(75.0);

    $anakArea = $tree['anak']->firstWhere('kode', 200);
    expect($anakArea)->not->toBeNull();
    expect($anakArea['total_aset'])->toBe(2);

    $cucuKc = $anakArea['anak']->firstWhere('kode', 300);
    expect($cucuKc)->not->toBeNull();
    expect($cucuKc['total_aset'])->toBe(2);
    expect($cucuKc['rata_compliance'])->toBe(75.0);
});

test('endpoint detail AJAX menjumlahkan aset dari node beserta seluruh keturunannya', function () {
    $admin = User::factory()->admin()->create();
    [$kanwil, $area, $kc] = buatStrukturUkerUjiCoba();

    $kodeAset = KodeAset::factory()->create(['kategori' => 'PERSONAL COMPUTER']);
    Aset::factory()->count(2)->create(['uker_kode' => $kc->kode, 'kode_aset_kode' => $kodeAset->kode]);

    // Minta detail dari Area (kode 200) -- harus ikut ngerangkum KC (300) di bawahnya
    $response = $this->actingAs($admin)->getJson(route('uker-tree.detail', $area->kode));

    $response->assertOk();
    $response->assertJson([
        'nama' => 'Area Uji',
        'total_aset' => 2,
        'jumlah_unit_total' => 2, // area itu sendiri + kc
        'jumlah_unit_ada_aset' => 1,
    ]);
});

test('endpoint detail mengembalikan 404 untuk kode uker yang tidak ada', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->getJson(route('uker-tree.detail', 999999))->assertNotFound();
});

test('endpoint compliance detail memecah compliance per kategori checklist', function () {
    $admin = User::factory()->admin()->create();
    [$kanwil, $area, $kc] = buatStrukturUkerUjiCoba();

    $form = HealthCheckForm::factory()->create(['uker_kode' => $kc->kode]);
    HealthCheckItem::factory()->count(3)->create([
        'health_check_form_id' => $form->id,
        'kategori' => 'A - Ruang Server/Jaringan',
        'status' => 'OK',
    ]);
    HealthCheckItem::factory()->count(1)->create([
        'health_check_form_id' => $form->id,
        'kategori' => 'A - Ruang Server/Jaringan',
        'status' => 'Not OK',
    ]);

    $response = $this->actingAs($admin)->getJson(route('uker-tree.complianceDetail', $kc->kode));

    $response->assertOk();
    $response->assertJsonFragment(['rata_compliance' => 75.0]);
    $response->assertJsonFragment(['label' => 'A - Ruang Server/Jaringan', 'total' => 4, 'ok' => 3, 'persen' => 75.0]);
    $response->assertJsonFragment(['label' => 'Draft', 'jumlah' => 1]);
    $response->assertJsonFragment(['label' => 'Belum Ditindaklanjuti', 'jumlah' => 1]);
});

// ===================== Batasan akses role "user" (subtree sendiri) =====================

test('user Cabang A bisa akses detail() untuk dirinya sendiri dan turunannya (KCP A1)', function () {
    $s = buatStrukturCabangUjiCoba();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    $this->actingAs($user)->getJson(route('uker-tree.detail', $s['cabangA']->kode))->assertOk();
    $this->actingAs($user)->getJson(route('uker-tree.detail', $s['kcpA1']->kode))->assertOk();
});

test('user Cabang A DITOLAK (403) akses detail() milik Cabang B, walau tahu uker_kode-nya lewat request langsung', function () {
    $s = buatStrukturCabangUjiCoba();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    $this->actingAs($user)->getJson(route('uker-tree.detail', $s['cabangB']->kode))->assertForbidden();
});

test('user Cabang A DITOLAK (403) akses detail() milik turunan Cabang B (KCP B1)', function () {
    $s = buatStrukturCabangUjiCoba();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    $this->actingAs($user)->getJson(route('uker-tree.detail', $s['kcpB1']->kode))->assertForbidden();
});

test('user Cabang A bisa akses complianceDetail() untuk dirinya sendiri dan turunannya, tapi DITOLAK buat Cabang B', function () {
    $s = buatStrukturCabangUjiCoba();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    $this->actingAs($user)->getJson(route('uker-tree.complianceDetail', $s['cabangA']->kode))->assertOk();
    $this->actingAs($user)->getJson(route('uker-tree.complianceDetail', $s['kcpA1']->kode))->assertOk();
    $this->actingAs($user)->getJson(route('uker-tree.complianceDetail', $s['cabangB']->kode))->assertForbidden();
    $this->actingAs($user)->getJson(route('uker-tree.complianceDetail', $s['kcpB1']->kode))->assertForbidden();
});

test('admin tetap bisa akses detail()/complianceDetail() uker manapun, gak berubah', function () {
    $s = buatStrukturCabangUjiCoba();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->getJson(route('uker-tree.detail', $s['cabangA']->kode))->assertOk();
    $this->actingAs($admin)->getJson(route('uker-tree.detail', $s['cabangB']->kode))->assertOk();
    $this->actingAs($admin)->getJson(route('uker-tree.complianceDetail', $s['kcpA1']->kode))->assertOk();
    $this->actingAs($admin)->getJson(route('uker-tree.complianceDetail', $s['kcpB1']->kode))->assertOk();
});

// ===================== Export =====================
// Beda dari export lain di app ini yang admin-only -- export Struktur
// Organisasi ngikutin scope akses HALAMAN-nya (semua role bisa), bukan
// role:admin, karena non-admin juga boleh liat/export subtree sendiri.

test('user biasa BISA export struktur organisasi (root subtree sendiri, bukan admin-only)', function () {
    $s = buatStrukturCabangUjiCoba();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    $this->actingAs($user)->get(route('uker-tree.export.excel'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($user)->get(route('uker-tree.export.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('export Excel struktur organisasi user biasa cuma isi subtree sendiri, bukan cabang lain', function () {
    $s = buatStrukturCabangUjiCoba();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    $response = $this->actingAs($user)->get(route('uker-tree.export.excel'));
    $response->assertOk();

    // PDF isinya binary terkompresi (gak bisa di-assertSee langsung), jadi
    // verifikasi isi datanya lewat Excel yang bisa dibaca ulang pakai
    // PhpSpreadsheet -- lebih reliable daripada nebak-nebak byte PDF.
    $tmpFile = tempnam(sys_get_temp_dir(), 'export').'.xlsx';
    file_put_contents($tmpFile, $response->streamedContent());
    $sheet = IOFactory::load($tmpFile)->getActiveSheet();
    $isiSheet = implode(' | ', $sheet->toArray()[0] ?? []);
    for ($i = 1; $i < $sheet->getHighestRow(); $i++) {
        $isiSheet .= ' | '.implode(' | ', $sheet->toArray()[$i] ?? []);
    }
    unlink($tmpFile);

    expect($isiSheet)->toContain('Cabang A', 'KCP A1');
    expect($isiSheet)->not->toContain('Cabang B');
});

test('admin bisa export struktur organisasi lengkap dari Kanwil', function () {
    buatStrukturUkerUjiCoba();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('uker-tree.export.excel'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});
