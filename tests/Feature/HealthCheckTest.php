<?php

use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\Uker;
use App\Models\User;

test('guest tidak bisa akses daftar health check', function () {
    $this->get(route('healthcheck.index'))->assertRedirect(route('login'));
});

test('store form health check otomatis generate 61 item checklist', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $response = $this->actingAs($user)->post(route('healthcheck.store'), [
        'uker_kode' => $uker->kode,
        'tanggal_pemeriksaan' => now()->toDateString(),
        'periode' => 'Triwulan I 2026',
    ]);

    $form = HealthCheckForm::first();
    $response->assertRedirect(route('healthcheck.edit', $form));
    expect($form->items()->count())->toBe(61);
    expect($form->items()->where('status', 'Belum Diperiksa')->count())->toBe(61);
});

test('user tidak bisa membuat form health check untuk uker lain', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();

    $response = $this->actingAs($user)->post(route('healthcheck.store'), [
        'uker_kode' => $ukerLain->kode,
        'tanggal_pemeriksaan' => now()->toDateString(),
        'periode' => 'Triwulan I 2026',
    ]);

    $response->assertForbidden();
    expect(HealthCheckForm::count())->toBe(0);
});

test('user cuma melihat form health check dari uker sendiri', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();
    HealthCheckForm::factory()->create(['uker_kode' => $ukerSendiri->kode]);
    HealthCheckForm::factory()->create(['uker_kode' => $ukerLain->kode]);

    $response = $this->actingAs($user)->get(route('healthcheck.index'));

    $response->assertOk();
    expect($response->viewData('formList')->total())->toBe(1);
});

test('user tidak bisa mengakses form health check milik uker lain', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $ukerLain->kode]);

    $this->actingAs($user)->get(route('healthcheck.edit', $form))->assertForbidden();
    $this->actingAs($user)->delete(route('healthcheck.destroy', $form))->assertForbidden();

    expect(HealthCheckForm::find($form->id))->not->toBeNull();
});

test('update menyimpan status dan catatan tiap item pemeriksaan', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create([
        'uker_kode' => $uker->kode,
        'tanggal_pemeriksaan' => now()->toDateString(),
    ]);
    $item = HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id,
        'kategori' => 'A - Ruang Server/Jaringan',
    ]);

    $response = $this->actingAs($user)->put(route('healthcheck.update', $form), [
        'items' => [
            ['id' => $item->id, 'status' => 'Not OK', 'catatan' => 'AC mati'],
        ],
        'status_tindak_lanjut' => 'Sedang Diproses',
        'catatan_tindak_lanjut' => 'Sudah diajukan perbaikan AC ke vendor.',
    ]);

    $response->assertRedirect(route('healthcheck.index'));
    expect($item->fresh()->status)->toBe('Not OK');
    expect($item->fresh()->catatan)->toBe('AC mati');
    expect($form->fresh()->status_tindak_lanjut)->toBe('Sedang Diproses');
    expect($form->fresh()->catatan_tindak_lanjut)->toBe('Sudah diajukan perbaikan AC ke vendor.');
});

test('item checklist terkunci kalau tanggal pemeriksaan sudah lewat, tapi status tindak lanjut tetap bisa diupdate', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create([
        'uker_kode' => $uker->kode,
        'tanggal_pemeriksaan' => now()->subDay()->toDateString(),
    ]);
    $item = HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id,
        'status' => 'Belum Diperiksa',
    ]);

    $response = $this->actingAs($user)->put(route('healthcheck.update', $form), [
        'items' => [
            ['id' => $item->id, 'status' => 'Not OK', 'catatan' => 'AC mati'],
        ],
        'status_tindak_lanjut' => 'Sedang Diproses',
    ]);

    $response->assertRedirect(route('healthcheck.index'));
    expect($item->fresh()->status)->toBe('Belum Diperiksa');
    expect($form->fresh()->status_tindak_lanjut)->toBe('Sedang Diproses');
});

test('update tanpa status_tindak_lanjut ditolak validasi', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id]);

    $response = $this->actingAs($user)->put(route('healthcheck.update', $form), [
        'items' => [
            ['id' => $item->id, 'status' => 'OK'],
        ],
    ]);

    $response->assertSessionHasErrors('status_tindak_lanjut');
});

test('destroy form health check ikut menghapus semua item-nya', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    HealthCheckItem::factory()->count(3)->create(['health_check_form_id' => $form->id]);

    $this->actingAs($user)->delete(route('healthcheck.destroy', $form))->assertRedirect(route('healthcheck.index'));

    expect(HealthCheckForm::find($form->id))->toBeNull();
    expect(HealthCheckItem::where('health_check_form_id', $form->id)->count())->toBe(0);
});
