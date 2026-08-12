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

test('gak bisa bikin form health check dobel buat uker & periode yang sama', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'periode' => 'Triwulan I 2026']);

    $response = $this->actingAs($user)->post(route('healthcheck.store'), [
        'uker_kode' => $uker->kode,
        'tanggal_pemeriksaan' => now()->toDateString(),
        'periode' => 'Triwulan I 2026',
    ]);

    $response->assertSessionHasErrors('periode');
    expect(HealthCheckForm::where('uker_kode', $uker->kode)->count())->toBe(1);
});

test('periode yang sama tetap boleh dipakai uker lain', function () {
    $ukerA = Uker::factory()->create();
    $ukerB = Uker::factory()->create();
    $userB = User::factory()->forUker($ukerB->kode)->create();
    HealthCheckForm::factory()->create(['uker_kode' => $ukerA->kode, 'periode' => 'Triwulan I 2026']);

    $response = $this->actingAs($userB)->post(route('healthcheck.store'), [
        'uker_kode' => $ukerB->kode,
        'tanggal_pemeriksaan' => now()->toDateString(),
        'periode' => 'Triwulan I 2026',
    ]);

    $response->assertRedirect();
    expect(HealthCheckForm::where('uker_kode', $ukerB->kode)->count())->toBe(1);
});

test('form yang sudah dihapus (soft delete) boleh dibuat ulang dengan periode sama', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $formLama = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'periode' => 'Triwulan I 2026']);
    $formLama->delete();

    $response = $this->actingAs($user)->post(route('healthcheck.store'), [
        'uker_kode' => $uker->kode,
        'tanggal_pemeriksaan' => now()->toDateString(),
        'periode' => 'Triwulan I 2026',
    ]);

    $response->assertRedirect();
    expect(HealthCheckForm::where('uker_kode', $uker->kode)->count())->toBe(1);
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

test('dokumentasi visual (kategori E) bisa disimpan dan tampil ulang', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create([
        'uker_kode' => $uker->kode,
        'tanggal_pemeriksaan' => now()->toDateString(),
    ]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id]);

    $response = $this->actingAs($user)->put(route('healthcheck.update', $form), [
        'items' => [
            ['id' => $item->id, 'status' => 'OK'],
        ],
        'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
        'foto_ruang_server_url' => 'https://contoh.com/foto-ruang-server.jpg',
        'foto_storage_cctv_url' => 'https://contoh.com/foto-storage-cctv.jpg',
        'foto_panel_ups_url' => 'https://contoh.com/foto-panel-ups.jpg',
    ]);

    $response->assertRedirect(route('healthcheck.index'));
    $form->refresh();
    expect($form->foto_ruang_server_url)->toBe('https://contoh.com/foto-ruang-server.jpg');
    expect($form->foto_storage_cctv_url)->toBe('https://contoh.com/foto-storage-cctv.jpg');
    expect($form->foto_panel_ups_url)->toBe('https://contoh.com/foto-panel-ups.jpg');
    expect($form->jumlahFotoDokumentasiTerisi())->toBe(3);

    $this->actingAs($user)->get(route('healthcheck.edit', $form))
        ->assertOk()
        ->assertSee('https://contoh.com/foto-ruang-server.jpg', false);
});

test('compliance persen tidak berubah baik dokumentasi visual diisi maupun tidak', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create([
        'uker_kode' => $uker->kode,
        'tanggal_pemeriksaan' => now()->toDateString(),
    ]);
    $itemOk = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Belum Diperiksa']);
    $itemNotOk = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Belum Diperiksa']);

    // Update pertama: item diisi, dokumentasi visual TIDAK diisi sama sekali.
    $this->actingAs($user)->put(route('healthcheck.update', $form), [
        'items' => [
            ['id' => $itemOk->id, 'status' => 'OK'],
            ['id' => $itemNotOk->id, 'status' => 'Not OK', 'catatan' => 'AC mati'],
        ],
        'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
    ]);
    expect($form->fresh()->persenCompliance())->toBe(50.0);

    // Update kedua: item statusnya sama, tapi sekarang dokumentasi visual diisi.
    $this->actingAs($user)->put(route('healthcheck.update', $form), [
        'items' => [
            ['id' => $itemOk->id, 'status' => 'OK'],
            ['id' => $itemNotOk->id, 'status' => 'Not OK', 'catatan' => 'AC mati'],
        ],
        'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
        'foto_ruang_server_url' => 'https://contoh.com/a.jpg',
        'foto_storage_cctv_url' => 'https://contoh.com/b.jpg',
        'foto_panel_ups_url' => 'https://contoh.com/c.jpg',
    ]);

    // Compliance % harus tetap 50%, gak kepengaruh sama sekali oleh field dokumentasi visual.
    expect($form->fresh()->persenCompliance())->toBe(50.0);
});

test('dokumentasi visual ikut terkunci kalau tanggal pemeriksaan sudah lewat', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create([
        'uker_kode' => $uker->kode,
        'tanggal_pemeriksaan' => now()->subDay()->toDateString(),
    ]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id]);

    $response = $this->actingAs($user)->put(route('healthcheck.update', $form), [
        'items' => [
            ['id' => $item->id, 'status' => 'OK'],
        ],
        'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
        'foto_ruang_server_url' => 'https://contoh.com/tidak-boleh-tersimpan.jpg',
    ]);

    $response->assertRedirect(route('healthcheck.index'));
    expect($form->fresh()->foto_ruang_server_url)->toBeNull();
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

test('destroy form health check soft delete -- item-nya tetap tersimpan biar bisa dipulihkan utuh', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    HealthCheckItem::factory()->count(3)->create(['health_check_form_id' => $form->id]);

    $this->actingAs($user)->delete(route('healthcheck.destroy', $form))->assertRedirect(route('healthcheck.index'));

    expect(HealthCheckForm::find($form->id))->toBeNull(); // gak muncul di query normal
    expect(HealthCheckForm::onlyTrashed()->find($form->id))->not->toBeNull(); // tapi masih ada di database
    expect(HealthCheckItem::where('health_check_form_id', $form->id)->count())->toBe(3); // item-nya gak ikut hilang

    $this->actingAs($user)->post(route('healthcheck.restore', $form->id))->assertRedirect();
    expect($form->fresh()->items()->count())->toBe(3);
});

test('admin bisa lihat semua form di sampah, user cuma lihat punya uker sendiri', function () {
    $admin = User::factory()->admin()->create();
    $ukerA = Uker::factory()->create();
    $ukerB = Uker::factory()->create();
    $userA = User::factory()->forUker($ukerA->kode)->create();
    $formA = HealthCheckForm::factory()->create(['uker_kode' => $ukerA->kode]);
    $formB = HealthCheckForm::factory()->create(['uker_kode' => $ukerB->kode]);
    $formA->delete();
    $formB->delete();

    $responseAdmin = $this->actingAs($admin)->get(route('healthcheck.trash'));
    $responseAdmin->assertOk();
    expect($responseAdmin->viewData('formList')->total())->toBe(2);

    $responseUser = $this->actingAs($userA)->get(route('healthcheck.trash'));
    $responseUser->assertOk();
    expect($responseUser->viewData('formList')->total())->toBe(1);

    $this->actingAs($userA)->post(route('healthcheck.restore', $formB->id))->assertForbidden();
});

test('guest tidak bisa akses sampah maupun restore health check', function () {
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    $form->delete();

    $this->get(route('healthcheck.trash'))->assertRedirect(route('login'));
    $this->post(route('healthcheck.restore', $form->id))->assertRedirect(route('login'));
});
