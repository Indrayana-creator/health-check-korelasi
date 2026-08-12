<?php

use App\Models\Aset;
use App\Models\HealthCheckForm;
use App\Models\KodeAset;
use App\Models\Uker;
use App\Models\User;

// Bangun struktur organisasi buat semua test di file ini:
//
//   Kanwil
//   ├── Cabang A
//   │   ├── KCP A1
//   │   │   └── Unit A1a
//   │   └── KCP A2
//   └── Cabang B
//       └── KCP B1
//
// "Petugas Cabang A" (uker_kode = Cabang A) seharusnya bisa akses Cabang A +
// KCP A1 + Unit A1a + KCP A2 (subtree-nya sendiri), tapi TIDAK BISA akses
// Cabang B atau KCP B1 (subtree cabang lain).
function bangunStrukturOrganisasi(): array
{
    $kanwil = Uker::factory()->create(['nama' => 'Kanwil Percobaan']);
    $cabangA = Uker::factory()->create(['nama' => 'Cabang A', 'kode_spv' => $kanwil->kode]);
    $kcpA1 = Uker::factory()->create(['nama' => 'KCP A1', 'kode_spv' => $cabangA->kode]);
    $unitA1a = Uker::factory()->create(['nama' => 'Unit A1a', 'kode_spv' => $kcpA1->kode]);
    $kcpA2 = Uker::factory()->create(['nama' => 'KCP A2', 'kode_spv' => $cabangA->kode]);
    $cabangB = Uker::factory()->create(['nama' => 'Cabang B', 'kode_spv' => $kanwil->kode]);
    $kcpB1 = Uker::factory()->create(['nama' => 'KCP B1', 'kode_spv' => $cabangB->kode]);

    return compact('kanwil', 'cabangA', 'kcpA1', 'unitA1a', 'kcpA2', 'cabangB', 'kcpB1');
}

// ===================== Uker::descendantKodes() -- unit logic =====================

test('descendantKodes mengembalikan diri sendiri + semua turunan rekursif (anak & cucu), bukan cuma anak langsung', function () {
    $s = bangunStrukturOrganisasi();

    $hasil = Uker::descendantKodes($s['cabangA']->kode);

    expect($hasil)->toContain($s['cabangA']->kode, $s['kcpA1']->kode, $s['unitA1a']->kode, $s['kcpA2']->kode);
    expect($hasil)->toHaveCount(4); // Cabang A sendiri + KCP A1 + Unit A1a + KCP A2
});

test('descendantKodes TIDAK memasukkan cabang lain atau turunannya', function () {
    $s = bangunStrukturOrganisasi();

    $hasil = Uker::descendantKodes($s['cabangA']->kode);

    expect($hasil)->not->toContain($s['cabangB']->kode, $s['kcpB1']->kode, $s['kanwil']->kode);
});

test('descendantKodes untuk node daun (gak punya anak) cuma balikin dirinya sendiri', function () {
    $s = bangunStrukturOrganisasi();

    expect(Uker::descendantKodes($s['unitA1a']->kode))->toBe([$s['unitA1a']->kode]);
});

// ===================== Data Aset: listing (index) =====================

test('user Cabang A melihat aset milik Cabang A + seluruh turunannya (KCP, Unit) di listing', function () {
    $s = bangunStrukturOrganisasi();
    $kodeAset = KodeAset::factory()->create();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    Aset::factory()->create(['uker_kode' => $s['cabangA']->kode, 'kode_aset_kode' => $kodeAset->kode]);
    Aset::factory()->create(['uker_kode' => $s['kcpA1']->kode, 'kode_aset_kode' => $kodeAset->kode]);
    Aset::factory()->create(['uker_kode' => $s['unitA1a']->kode, 'kode_aset_kode' => $kodeAset->kode]);
    Aset::factory()->create(['uker_kode' => $s['kcpA2']->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $response = $this->actingAs($user)->get(route('aset.index'));

    $response->assertOk();
    expect($response->viewData('asetList')->total())->toBe(4);
});

test('user Cabang A TIDAK melihat aset milik Cabang B atau turunannya di listing', function () {
    $s = bangunStrukturOrganisasi();
    $kodeAset = KodeAset::factory()->create();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    Aset::factory()->create(['uker_kode' => $s['cabangA']->kode, 'kode_aset_kode' => $kodeAset->kode]);
    Aset::factory()->create(['uker_kode' => $s['cabangB']->kode, 'kode_aset_kode' => $kodeAset->kode]);
    Aset::factory()->create(['uker_kode' => $s['kcpB1']->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $response = $this->actingAs($user)->get(route('aset.index'));

    $response->assertOk();
    expect($response->viewData('asetList')->total())->toBe(1); // cuma punya Cabang A sendiri
});

// ===================== Data Aset: input baru (create/store) =====================

test('user Cabang A bisa lihat pilihan uker turunannya sendiri di dropdown form Tambah Aset', function () {
    $s = bangunStrukturOrganisasi();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    $response = $this->actingAs($user)->get(route('aset.create'));

    $response->assertOk();
    $kodeList = $response->viewData('ukerList')->pluck('kode');
    expect($kodeList)->toContain($s['cabangA']->kode, $s['kcpA1']->kode, $s['unitA1a']->kode, $s['kcpA2']->kode);
    expect($kodeList)->not->toContain($s['cabangB']->kode, $s['kcpB1']->kode);
});

test('user Cabang A bisa input aset baru untuk KCP di bawahnya (turunan, bukan cuma diri sendiri)', function () {
    $s = bangunStrukturOrganisasi();
    $kodeAset = KodeAset::factory()->create();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    $response = $this->actingAs($user)->post(route('aset.store'), [
        'uker_kode' => $s['kcpA1']->kode, // bukan uker_kode sendiri, tapi turunannya
        'kode_aset_kode' => $kodeAset->kode,
        'merek' => 'Dell',
        'tipe_model' => 'Latitude 5420',
        'sn' => 'SN-SUBTREE-001',
    ]);

    $response->assertRedirect(route('aset.index'));
    expect(Aset::where('sn', 'SN-SUBTREE-001')->where('uker_kode', $s['kcpA1']->kode)->exists())->toBeTrue();
});

test('user Cabang A TIDAK bisa input aset untuk Cabang B (bukan bagian dari subtree-nya), walau request dikirim langsung', function () {
    $s = bangunStrukturOrganisasi();
    $kodeAset = KodeAset::factory()->create();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    // Simulasi user memalsukan uker_kode lewat request langsung (bukan lewat
    // dropdown UI yang sudah difilter) -- server HARUS tetap menolak.
    $response = $this->actingAs($user)->post(route('aset.store'), [
        'uker_kode' => $s['cabangB']->kode,
        'kode_aset_kode' => $kodeAset->kode,
        'merek' => 'Dell',
        'tipe_model' => 'Latitude 5420',
        'sn' => 'SN-TOLAK-001',
    ]);

    $response->assertForbidden();
    expect(Aset::where('sn', 'SN-TOLAK-001')->exists())->toBeFalse();
});

test('user Cabang A TIDAK bisa input aset untuk turunan Cabang B (KCP B1), walau bukan Cabang B langsung', function () {
    $s = bangunStrukturOrganisasi();
    $kodeAset = KodeAset::factory()->create();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    $response = $this->actingAs($user)->post(route('aset.store'), [
        'uker_kode' => $s['kcpB1']->kode,
        'kode_aset_kode' => $kodeAset->kode,
        'merek' => 'Dell',
        'tipe_model' => 'Latitude 5420',
        'sn' => 'SN-TOLAK-002',
    ]);

    $response->assertForbidden();
    expect(Aset::where('sn', 'SN-TOLAK-002')->exists())->toBeFalse();
});

// ===================== Data Aset: akses ke record yang sudah ada (Policy) =====================

test('user Cabang A bisa edit aset milik cucu-nya (Unit A1a), bukan cuma anak langsung', function () {
    $s = bangunStrukturOrganisasi();
    $kodeAset = KodeAset::factory()->create();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();
    $aset = Aset::factory()->create(['uker_kode' => $s['unitA1a']->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $this->actingAs($user)->get(route('aset.edit', $aset))->assertOk();
});

test('user Cabang A TIDAK bisa edit atau hapus aset milik Cabang B', function () {
    $s = bangunStrukturOrganisasi();
    $kodeAset = KodeAset::factory()->create();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();
    $aset = Aset::factory()->create(['uker_kode' => $s['cabangB']->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $this->actingAs($user)->get(route('aset.edit', $aset))->assertForbidden();
    $this->actingAs($user)->delete(route('aset.destroy', $aset))->assertForbidden();
    expect(Aset::find($aset->id))->not->toBeNull();
});

// ===================== Health Check: listing (index) =====================

test('user Cabang A melihat form Health Check milik Cabang A + seluruh turunannya di listing', function () {
    $s = bangunStrukturOrganisasi();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    HealthCheckForm::factory()->create(['uker_kode' => $s['cabangA']->kode]);
    HealthCheckForm::factory()->create(['uker_kode' => $s['kcpA1']->kode]);
    HealthCheckForm::factory()->create(['uker_kode' => $s['unitA1a']->kode]);

    $response = $this->actingAs($user)->get(route('healthcheck.index'));

    $response->assertOk();
    expect($response->viewData('formList')->total())->toBe(3);
});

test('user Cabang A TIDAK melihat form Health Check milik Cabang B atau turunannya', function () {
    $s = bangunStrukturOrganisasi();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    HealthCheckForm::factory()->create(['uker_kode' => $s['cabangA']->kode]);
    HealthCheckForm::factory()->create(['uker_kode' => $s['cabangB']->kode]);
    HealthCheckForm::factory()->create(['uker_kode' => $s['kcpB1']->kode]);

    $response = $this->actingAs($user)->get(route('healthcheck.index'));

    $response->assertOk();
    expect($response->viewData('formList')->total())->toBe(1);
});

// ===================== Health Check: input baru (create/store) =====================

test('user Cabang A bisa buat form Health Check untuk KCP di bawahnya (turunan)', function () {
    $s = bangunStrukturOrganisasi();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    $response = $this->actingAs($user)->post(route('healthcheck.store'), [
        'uker_kode' => $s['kcpA2']->kode,
        'tanggal_pemeriksaan' => now()->toDateString(),
        'periode' => 'Uji Subtree 2026',
    ]);

    $form = HealthCheckForm::where('uker_kode', $s['kcpA2']->kode)->first();
    expect($form)->not->toBeNull();
    $response->assertRedirect(route('healthcheck.edit', $form));
});

test('user Cabang A TIDAK bisa buat form Health Check untuk Cabang B, walau request dikirim langsung', function () {
    $s = bangunStrukturOrganisasi();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    $response = $this->actingAs($user)->post(route('healthcheck.store'), [
        'uker_kode' => $s['cabangB']->kode,
        'tanggal_pemeriksaan' => now()->toDateString(),
        'periode' => 'Uji Tolak 2026',
    ]);

    $response->assertForbidden();
    expect(HealthCheckForm::where('uker_kode', $s['cabangB']->kode)->where('periode', 'Uji Tolak 2026')->exists())->toBeFalse();
});

test('user Cabang A TIDAK bisa buat form Health Check untuk turunan Cabang B (KCP B1)', function () {
    $s = bangunStrukturOrganisasi();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    $response = $this->actingAs($user)->post(route('healthcheck.store'), [
        'uker_kode' => $s['kcpB1']->kode,
        'tanggal_pemeriksaan' => now()->toDateString(),
        'periode' => 'Uji Tolak 2026',
    ]);

    $response->assertForbidden();
    expect(HealthCheckForm::where('uker_kode', $s['kcpB1']->kode)->exists())->toBeFalse();
});

// ===================== Health Check: akses ke record yang sudah ada (Policy) =====================

test('user Cabang A bisa akses form Health Check milik cucu-nya (Unit A1a)', function () {
    $s = bangunStrukturOrganisasi();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $s['unitA1a']->kode]);

    $this->actingAs($user)->get(route('healthcheck.edit', $form))->assertOk();
});

test('user Cabang A TIDAK bisa akses form Health Check milik Cabang B', function () {
    $s = bangunStrukturOrganisasi();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $s['cabangB']->kode]);

    $this->actingAs($user)->get(route('healthcheck.edit', $form))->assertForbidden();
    $this->actingAs($user)->delete(route('healthcheck.destroy', $form))->assertForbidden();
    expect(HealthCheckForm::find($form->id))->not->toBeNull();
});

// ===================== Dashboard: agregat subtree =====================

test('dashboard non-admin menghitung total aset dari uker sendiri + seluruh turunannya', function () {
    $s = bangunStrukturOrganisasi();
    $kodeAset = KodeAset::factory()->create();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    Aset::factory()->create(['uker_kode' => $s['cabangA']->kode, 'kode_aset_kode' => $kodeAset->kode]);
    Aset::factory()->create(['uker_kode' => $s['kcpA1']->kode, 'kode_aset_kode' => $kodeAset->kode]);
    Aset::factory()->create(['uker_kode' => $s['unitA1a']->kode, 'kode_aset_kode' => $kodeAset->kode]);
    Aset::factory()->create(['uker_kode' => $s['cabangB']->kode, 'kode_aset_kode' => $kodeAset->kode]); // gak boleh ikut

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    expect($response->viewData('totalAset'))->toBe(3);
});

// ===================== Pencarian global: subtree =====================

test('pencarian global non-admin cuma nemuin aset dari subtree sendiri, bukan cabang lain', function () {
    $s = bangunStrukturOrganisasi();
    $kodeAset = KodeAset::factory()->create();
    $user = User::factory()->forUker($s['cabangA']->kode)->create();

    Aset::factory()->create(['uker_kode' => $s['kcpA1']->kode, 'kode_aset_kode' => $kodeAset->kode, 'no_asset' => 'CARI-SUBTREE-001']);
    Aset::factory()->create(['uker_kode' => $s['cabangB']->kode, 'kode_aset_kode' => $kodeAset->kode, 'no_asset' => 'CARI-SUBTREE-002']);

    $response = $this->actingAs($user)->getJson(route('search.api', ['q' => 'CARI-SUBTREE']));

    $response->assertOk();
    $hasil = collect($response->json('aset'))->pluck('title');
    expect($hasil)->toContain('CARI-SUBTREE-001');
    expect($hasil)->not->toContain('CARI-SUBTREE-002');
});
