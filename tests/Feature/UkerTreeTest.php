<?php

use App\Models\Aset;
use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\KodeAset;
use App\Models\Uker;
use App\Models\User;

// bangunTreeUker() (dipakai di /uker-tree & dashboard) selalu mulai dari
// Kanwil kode 146 sebagai root -- jadi tiap test yang butuh tree beneran
// harus bikin Uker dengan kode itu persis.
function buatStrukturUkerUjiCoba(): array
{
    $kanwil = Uker::factory()->create(['kode' => 146, 'nama' => 'Kanwil Uji', 'jenis' => 'KANWIL', 'kode_spv' => 146]);
    $area = Uker::factory()->create(['kode' => 200, 'nama' => 'Area Uji', 'jenis' => 'AREA', 'kode_spv' => 146]);
    $kc = Uker::factory()->create(['kode' => 300, 'nama' => 'KC Uji', 'jenis' => 'KC', 'kode_spv' => 200]);

    return [$kanwil, $area, $kc];
}

test('guest tidak bisa akses struktur organisasi', function () {
    $this->get(route('uker-tree.index'))->assertRedirect(route('login'));
});

test('user biasa tidak bisa akses struktur organisasi maupun endpoint detailnya', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('uker-tree.index'))->assertForbidden();
    $this->actingAs($user)->get(route('uker-tree.detail', $uker->kode))->assertForbidden();
    $this->actingAs($user)->get(route('uker-tree.complianceDetail', $uker->kode))->assertForbidden();
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
