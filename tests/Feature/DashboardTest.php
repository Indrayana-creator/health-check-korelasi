<?php

use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
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
