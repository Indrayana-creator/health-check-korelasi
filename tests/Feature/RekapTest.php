<?php

use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
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
