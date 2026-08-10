<?php

use App\Models\Aset;
use App\Models\HealthCheckForm;
use App\Models\KodeAset;
use App\Models\Uker;
use App\Models\User;

test('guest tidak bisa akses pencarian global', function () {
    $this->getJson(route('search.api', ['q' => 'test']))->assertUnauthorized();
});

test('pencarian dengan kurang dari 2 karakter mengembalikan hasil kosong', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $response = $this->actingAs($user)->getJson(route('search.api', ['q' => 'a']));

    $response->assertOk();
    $response->assertJson(['aset' => [], 'healthcheck' => []]);
});

test('admin bisa mencari aset dan health check dari semua uker', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();

    Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'no_asset' => 'Z5-K-CARIAKU-0001']);
    HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'periode' => 'Periode CariAku']);

    $responseAset = $this->actingAs($admin)->getJson(route('search.api', ['q' => 'CARIAKU']));
    $responseAset->assertOk();
    expect($responseAset->json('aset'))->toHaveCount(1);
    expect($responseAset->json('healthcheck'))->toHaveCount(1);
});

test('user biasa cuma bisa nemu aset dan health check dari uker sendiri', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();
    $kodeAset = KodeAset::factory()->create();

    Aset::factory()->create(['uker_kode' => $ukerSendiri->kode, 'kode_aset_kode' => $kodeAset->kode, 'no_asset' => 'Z5-K-SAMAKATA-0001']);
    Aset::factory()->create(['uker_kode' => $ukerLain->kode, 'kode_aset_kode' => $kodeAset->kode, 'no_asset' => 'Z5-K-SAMAKATA-0002']);

    $response = $this->actingAs($user)->getJson(route('search.api', ['q' => 'SAMAKATA']));

    $response->assertOk();
    expect($response->json('aset'))->toHaveCount(1);
    expect($response->json('aset.0.title'))->toBe('Z5-K-SAMAKATA-0001');
});

test('pencarian aset yang sudah di-soft-delete tidak ikut muncul', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'no_asset' => 'Z5-K-KEHAPUS-0001']);
    $aset->delete();

    $response = $this->actingAs($admin)->getJson(route('search.api', ['q' => 'KEHAPUS']));

    $response->assertOk();
    expect($response->json('aset'))->toHaveCount(0);
});
