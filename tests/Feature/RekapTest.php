<?php

use App\Models\Aset;
use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\KodeAset;
use App\Models\Uker;
use App\Models\User;

test('user biasa tidak bisa akses rekap cabang', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('rekap.cabang'))->assertForbidden();
});

test('rekap menjumlahkan compliance semua uker dalam satu cabang (uker_spv)', function () {
    $admin = User::factory()->admin()->create();

    $ukerA = Uker::factory()->create(['uker_spv' => 'Cabang Surabaya']);
    $ukerB = Uker::factory()->create(['uker_spv' => 'Cabang Surabaya']);

    $formA = HealthCheckForm::factory()->create(['uker_kode' => $ukerA->kode]);
    HealthCheckItem::factory()->count(2)->create(['health_check_form_id' => $formA->id, 'status' => 'OK']);
    HealthCheckItem::factory()->count(2)->create(['health_check_form_id' => $formA->id, 'status' => 'Not OK']);

    $formB = HealthCheckForm::factory()->create(['uker_kode' => $ukerB->kode]);
    HealthCheckItem::factory()->count(4)->create(['health_check_form_id' => $formB->id, 'status' => 'OK']);

    $response = $this->actingAs($admin)->get(route('rekap.cabang'));

    $response->assertOk();
    $rekap = $response->viewData('rekap')->firstWhere('cabang', 'Cabang Surabaya');

    expect($rekap)->not->toBeNull();
    expect($rekap['jumlah_uker_lapor'])->toBe(2);
    expect($rekap['total_item'])->toBe(8);
    expect($rekap['ok'])->toBe(6);
    // 6 OK dari 8 total = 75%, di bawah ambang 80% jadi "PERLU PERHATIAN"
    expect($rekap['persen'])->toBe(75.0);
    expect($rekap['status'])->toBe('PERLU PERHATIAN');
});

test('tren compliance diurutkan kronologis berdasarkan tanggal pemeriksaan paling awal, bukan alfabetis periode', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();

    // "Agustus" duluan dibuat tapi tanggalnya belakangan -- alfabetis bakal
    // salah urutan (A < J), jadi harus ngikutin tanggal_pemeriksaan
    $formAgustus = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'periode' => 'Agustus 2026', 'tanggal_pemeriksaan' => '2026-08-05']);
    HealthCheckItem::factory()->count(4)->create(['health_check_form_id' => $formAgustus->id, 'status' => 'OK']);

    $formJuli = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'periode' => 'Juli 2026', 'tanggal_pemeriksaan' => '2026-07-10']);
    HealthCheckItem::factory()->count(2)->create(['health_check_form_id' => $formJuli->id, 'status' => 'OK']);
    HealthCheckItem::factory()->count(2)->create(['health_check_form_id' => $formJuli->id, 'status' => 'Not OK']);

    $response = $this->actingAs($admin)->get(route('rekap.cabang'));

    $response->assertOk();
    $tren = $response->viewData('trenCompliance');

    expect($tren)->toHaveCount(2);
    expect($tren[0]['periode'])->toBe('Juli 2026');
    expect($tren[0]['persen'])->toBe(50.0);
    expect($tren[1]['periode'])->toBe('Agustus 2026');
    expect($tren[1]['persen'])->toBe(100.0);
});

test('user biasa tidak bisa akses rekap aset per cabang', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('rekap.aset'))->assertForbidden();
});

test('rekap aset menjumlahkan kondisi aset semua uker dalam satu cabang (uker_spv)', function () {
    $admin = User::factory()->admin()->create();
    $kodeAset = KodeAset::factory()->create();

    $ukerA = Uker::factory()->create(['uker_spv' => 'Cabang Surabaya']);
    $ukerB = Uker::factory()->create(['uker_spv' => 'Cabang Surabaya']);

    Aset::factory()->count(6)->create(['uker_kode' => $ukerA->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'NORMAL']);
    Aset::factory()->count(2)->create(['uker_kode' => $ukerA->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'RUSAK']);
    Aset::factory()->count(1)->create(['uker_kode' => $ukerB->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'TIDAK LAYAK']);
    Aset::factory()->count(1)->create(['uker_kode' => $ukerB->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'BACKUP']);

    $response = $this->actingAs($admin)->get(route('rekap.aset'));

    $response->assertOk();
    $rekap = $response->viewData('rekap')->firstWhere('cabang', 'Cabang Surabaya');

    expect($rekap)->not->toBeNull();
    expect($rekap['jumlah_uker_lapor'])->toBe(2);
    expect($rekap['total'])->toBe(10);
    expect($rekap['normal'])->toBe(6);
    expect($rekap['rusak'])->toBe(2);
    expect($rekap['tidak_layak'])->toBe(1);
    expect($rekap['lainnya'])->toBe(1); // BACKUP, gak masuk 3 kategori spesifik di atas
    // 6 normal dari 10 total = 60%, di bawah ambang 80% jadi "PERLU PERHATIAN"
    expect($rekap['persen_sehat'])->toBe(60.0);
    expect($rekap['status'])->toBe('PERLU PERHATIAN');

    $distribusiKondisi = $response->viewData('distribusiKondisi');
    expect($distribusiKondisi)->toBe([
        'Normal' => 6,
        'Rusak' => 2,
        'Tidak Layak' => 1,
        'Lainnya' => 1,
    ]);
});
