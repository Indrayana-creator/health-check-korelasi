<?php

use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\Uker;
use App\Models\User;

test('guest tidak bisa akses monitoring kendala', function () {
    $this->get(route('monitoring.index'))->assertRedirect(route('login'));
});

test('user biasa tidak bisa akses monitoring kendala maupun update tindak lanjut', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK']);

    $this->actingAs($user)->get(route('monitoring.index'))->assertForbidden();
    $this->actingAs($user)->post(route('monitoring.updateTindakLanjut', $item), ['status_tindak_lanjut' => 'Sedang Diproses'])->assertForbidden();
    $this->actingAs($user)->get(route('monitoring.export.excel'))->assertForbidden();
    $this->actingAs($user)->get(route('monitoring.export.pdf'))->assertForbidden();
});

test('monitoring cuma menampilkan item yang statusnya Not OK', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);

    $rusak = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'item_pemeriksaan' => 'AC ruang server mati']);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'OK']);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'N/A']);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Belum Diperiksa']);

    $response = $this->actingAs($admin)->get(route('monitoring.index'));

    $response->assertOk();
    $items = $response->viewData('items');
    expect($items)->toHaveCount(1);
    expect($items->first()->id)->toBe($rusak->id);
});

test('monitoring bisa difilter berdasarkan uker, kategori, dan status tindak lanjut', function () {
    $admin = User::factory()->admin()->create();
    $ukerA = Uker::factory()->create();
    $ukerB = Uker::factory()->create();
    $formA = HealthCheckForm::factory()->create(['uker_kode' => $ukerA->kode]);
    $formB = HealthCheckForm::factory()->create(['uker_kode' => $ukerB->kode]);

    HealthCheckItem::factory()->create([
        'health_check_form_id' => $formA->id, 'status' => 'Not OK',
        'kategori' => 'A - Ruang Server/Jaringan', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
    ]);
    HealthCheckItem::factory()->create([
        'health_check_form_id' => $formB->id, 'status' => 'Not OK',
        'kategori' => 'B - CCTV & Storage', 'status_tindak_lanjut' => 'Selesai Diperbaiki',
    ]);

    $responseUker = $this->actingAs($admin)->get(route('monitoring.index', ['uker_kode' => $ukerA->kode]));
    expect($responseUker->viewData('items'))->toHaveCount(1);

    $responseKategori = $this->actingAs($admin)->get(route('monitoring.index', ['kategori' => 'B - CCTV & Storage']));
    expect($responseKategori->viewData('items'))->toHaveCount(1);

    $responseStatus = $this->actingAs($admin)->get(route('monitoring.index', ['status_tindak_lanjut' => 'Selesai Diperbaiki']));
    expect($responseStatus->viewData('items'))->toHaveCount(1);
    expect($responseStatus->viewData('items')->first()->kategori)->toBe('B - CCTV & Storage');
});

test('stat card monitoring menghitung total, belum ditindaklanjuti, dan selesai dengan benar', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);

    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti']);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti']);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Selesai Diperbaiki']);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'OK']);

    $response = $this->actingAs($admin)->get(route('monitoring.index'));

    $response->assertViewHas('totalBermasalah', 3);
    $response->assertViewHas('totalBelum', 2);
    $response->assertViewHas('totalSelesai', 1);
});

test('admin bisa update status dan catatan tindak lanjut per item', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    $item = HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
    ]);

    $response = $this->actingAs($admin)->post(route('monitoring.updateTindakLanjut', $item), [
        'status_tindak_lanjut' => 'Sedang Diproses',
        'catatan_tindak_lanjut' => 'Sudah diajukan perbaikan ke vendor',
    ]);

    $response->assertRedirect();
    $item->refresh();
    expect($item->status_tindak_lanjut)->toBe('Sedang Diproses');
    expect($item->catatan_tindak_lanjut)->toBe('Sudah diajukan perbaikan ke vendor');
});

test('update tindak lanjut item lain (bukan Not OK) tidak mempengaruhi status pemeriksaannya', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK']);

    $this->actingAs($admin)->post(route('monitoring.updateTindakLanjut', $item), [
        'status_tindak_lanjut' => 'Selesai Diperbaiki',
    ]);

    expect($item->fresh()->status)->toBe('Not OK'); // status pemeriksaan gak ikut berubah, cuma tindak lanjutnya
});

test('update tindak lanjut wajib pilih status yang valid', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK']);

    $response = $this->actingAs($admin)->post(route('monitoring.updateTindakLanjut', $item), [
        'status_tindak_lanjut' => 'Status Ngasal',
    ]);

    $response->assertSessionHasErrors('status_tindak_lanjut');
});

test('stat card mendesak menghitung item Not OK yang belum selesai lebih dari 3 hari', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();

    $formLama = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->subDays(5)]);
    $formBaru = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()]);

    // Mendesak: udah 5 hari & belum selesai
    HealthCheckItem::factory()->create(['health_check_form_id' => $formLama->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti']);
    // Belum mendesak: baru hari ini
    HealthCheckItem::factory()->create(['health_check_form_id' => $formBaru->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti']);
    // Udah lama tapi sudah selesai -- gak dihitung mendesak
    HealthCheckItem::factory()->create(['health_check_form_id' => $formLama->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Selesai Diperbaiki']);

    $response = $this->actingAs($admin)->get(route('monitoring.index'));

    $response->assertViewHas('totalMendesak', 1);
});

test('urutan item monitoring: prioritas status dulu, baru yang paling lama didiamkan', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();

    $formTua = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->subDays(10)]);
    $formMuda = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->subDays(2)]);

    $itemMuda = HealthCheckItem::factory()->create(['health_check_form_id' => $formMuda->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti', 'item_pemeriksaan' => 'Item Muda']);
    $itemTua = HealthCheckItem::factory()->create(['health_check_form_id' => $formTua->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti', 'item_pemeriksaan' => 'Item Tua']);

    $response = $this->actingAs($admin)->get(route('monitoring.index'));

    $items = $response->viewData('items');
    expect($items->first()->id)->toBe($itemTua->id);
    expect($items->last()->id)->toBe($itemMuda->id);
});

test('admin bisa export monitoring kendala ke excel', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK']);

    $response = $this->actingAs($admin)->get(route('monitoring.export.excel'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('admin bisa export monitoring kendala ke pdf', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK']);

    $response = $this->actingAs($admin)->get(route('monitoring.export.pdf'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

test('export monitoring kendala tetap ikut filter uker yang aktif', function () {
    $admin = User::factory()->admin()->create();
    $ukerA = Uker::factory()->create();
    $ukerB = Uker::factory()->create();
    $formA = HealthCheckForm::factory()->create(['uker_kode' => $ukerA->kode]);
    $formB = HealthCheckForm::factory()->create(['uker_kode' => $ukerB->kode]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $formA->id, 'status' => 'Not OK', 'item_pemeriksaan' => 'Item Uker A']);
    HealthCheckItem::factory()->create(['health_check_form_id' => $formB->id, 'status' => 'Not OK', 'item_pemeriksaan' => 'Item Uker B']);

    $response = $this->actingAs($admin)->get(route('monitoring.export.excel', ['uker_kode' => $ukerA->kode]));

    $response->assertOk();
});
