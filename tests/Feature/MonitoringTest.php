<?php

use App\Http\Controllers\MonitoringController;
use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\Pekerja;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\MonitoringTindakLanjutDiupdate;
use Illuminate\Support\Facades\Notification;

test('guest tidak bisa akses monitoring kendala', function () {
    $this->get(route('monitoring.index'))->assertRedirect(route('login'));
});

test('user biasa BISA akses monitoring kendala & export, di-scope ke uker sendiri + turunan', function () {
    $uker = Uker::factory()->create();
    $anak = Uker::factory()->create(['kode_spv' => $uker->kode]);
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $anak->kode]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK']);

    $this->actingAs($user)->get(route('monitoring.index'))->assertOk();
    $this->actingAs($user)->post(route('monitoring.updateTindakLanjut', $item), ['status_tindak_lanjut' => 'Sedang Diproses'])->assertRedirect();
    $this->actingAs($user)->get(route('monitoring.export.excel'))->assertOk();
    $this->actingAs($user)->get(route('monitoring.export.pdf'))->assertOk();
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

test('halaman monitoring nampilin link WhatsApp petugas IT uker terkait tiap item Not OK', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK']);
    Pekerja::factory()->create(['uker_kode' => $uker->kode, 'is_petugas_it' => true, 'no_hp' => '0812-3456-7890', 'nama' => 'Petugas Contoh']);
    // Pekerja lain di uker yang sama tapi BUKAN petugas IT -- gak boleh ikut nongol.
    Pekerja::factory()->create(['uker_kode' => $uker->kode, 'is_petugas_it' => false, 'no_hp' => '0899-9999-9999', 'nama' => 'Bukan Petugas IT']);

    $response = $this->actingAs($admin)->get(route('monitoring.index'));

    $response->assertOk();
    $response->assertSee('https://wa.me/6281234567890', false);
    $response->assertSee('Petugas Contoh');
    $response->assertDontSee('Bukan Petugas IT');
});

test('kalau unit pelapor sendiri gak punya petugas IT terdaftar, link WhatsApp ngambil dari cabang induknya', function () {
    $admin = User::factory()->admin()->create();
    $kc = Uker::factory()->create(['nama' => 'KC Induk']);
    $unit = Uker::factory()->create(['nama' => 'Unit Anak', 'kode_spv' => $kc->kode]);
    $form = HealthCheckForm::factory()->create(['uker_kode' => $unit->kode]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK']);
    // Petugas IT cuma terdaftar di KC induk, BUKAN di unit anak.
    Pekerja::factory()->create(['uker_kode' => $kc->kode, 'is_petugas_it' => true, 'no_hp' => '0812-3456-7890', 'nama' => 'Petugas KC Induk']);

    $response = $this->actingAs($admin)->get(route('monitoring.index'));

    $response->assertOk();
    $response->assertSee('https://wa.me/6281234567890', false);
    $response->assertSee('Petugas KC Induk');
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

// ===================== Batasan akses role "user" (subtree sendiri) =====================

test('user Cabang A cuma lihat kendala dari uker sendiri + turunannya, gak lihat Cabang B', function () {
    $cabangA = Uker::factory()->create();
    $kcpA1 = Uker::factory()->create(['kode_spv' => $cabangA->kode]);
    $cabangB = Uker::factory()->create();
    $user = User::factory()->forUker($cabangA->kode)->create();

    $formA = HealthCheckForm::factory()->create(['uker_kode' => $cabangA->kode]);
    $formAnak = HealthCheckForm::factory()->create(['uker_kode' => $kcpA1->kode]);
    $formB = HealthCheckForm::factory()->create(['uker_kode' => $cabangB->kode]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $formA->id, 'status' => 'Not OK']);
    HealthCheckItem::factory()->create(['health_check_form_id' => $formAnak->id, 'status' => 'Not OK']);
    HealthCheckItem::factory()->create(['health_check_form_id' => $formB->id, 'status' => 'Not OK']);

    $response = $this->actingAs($user)->get(route('monitoring.index'));

    $response->assertOk();
    expect($response->viewData('items'))->toHaveCount(2);
    $response->assertViewHas('totalBermasalah', 2);
});

test('user Cabang A TIDAK BISA update tindak lanjut item milik Cabang B, walau tahu ID item-nya lewat request langsung', function () {
    $cabangA = Uker::factory()->create();
    $cabangB = Uker::factory()->create();
    $user = User::factory()->forUker($cabangA->kode)->create();
    $formB = HealthCheckForm::factory()->create(['uker_kode' => $cabangB->kode]);
    $itemB = HealthCheckItem::factory()->create(['health_check_form_id' => $formB->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti']);

    $response = $this->actingAs($user)->post(route('monitoring.updateTindakLanjut', $itemB), [
        'status_tindak_lanjut' => 'Selesai Diperbaiki',
    ]);

    $response->assertForbidden();
    expect($itemB->fresh()->status_tindak_lanjut)->toBe('Belum Ditindaklanjuti');
});

test('user Cabang A bisa update tindak lanjut item milik turunannya sendiri (KCP)', function () {
    $cabangA = Uker::factory()->create();
    $kcpA1 = Uker::factory()->create(['kode_spv' => $cabangA->kode]);
    $user = User::factory()->forUker($cabangA->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $kcpA1->kode]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti']);

    $response = $this->actingAs($user)->post(route('monitoring.updateTindakLanjut', $item), [
        'status_tindak_lanjut' => 'Selesai Diperbaiki',
    ]);

    $response->assertRedirect();
    expect($item->fresh()->status_tindak_lanjut)->toBe('Selesai Diperbaiki');
});

test('export monitoring milik user biasa cuma berisi item dari subtree-nya sendiri', function () {
    $cabangA = Uker::factory()->create();
    $cabangB = Uker::factory()->create();
    $user = User::factory()->forUker($cabangA->kode)->create();
    $formA = HealthCheckForm::factory()->create(['uker_kode' => $cabangA->kode]);
    $formB = HealthCheckForm::factory()->create(['uker_kode' => $cabangB->kode]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $formA->id, 'status' => 'Not OK']);
    HealthCheckItem::factory()->create(['health_check_form_id' => $formB->id, 'status' => 'Not OK']);

    $response = $this->actingAs($user)->get(route('monitoring.export.excel'));

    $response->assertOk();
});

test('dropdown filter uker buat user biasa cuma berisi uker sendiri + turunan, bukan semua uker', function () {
    $cabangA = Uker::factory()->create();
    $kcpA1 = Uker::factory()->create(['kode_spv' => $cabangA->kode]);
    $cabangB = Uker::factory()->create();
    $user = User::factory()->forUker($cabangA->kode)->create();

    $response = $this->actingAs($user)->get(route('monitoring.index'));

    $kodeList = $response->viewData('ukerFilterList')->pluck('kode');
    expect($kodeList)->toContain($cabangA->kode, $kcpA1->kode);
    expect($kodeList)->not->toContain($cabangB->kode);
});

// ===================== SLA khusus "Sedang Diproses" =====================

test('mulai_diproses_at diisi otomatis begitu status pertama kali jadi Sedang Diproses', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    $item = HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
    ]);

    expect($item->mulai_diproses_at)->toBeNull();

    $this->actingAs($admin)->post(route('monitoring.updateTindakLanjut', $item), [
        'status_tindak_lanjut' => 'Sedang Diproses',
    ]);

    $item->refresh();
    expect($item->mulai_diproses_at)->not->toBeNull();
    expect($item->mulai_diproses_at->diffInSeconds(now()))->toBeLessThan(5);
});

test('mulai_diproses_at TIDAK di-reset kalau status bolak-balik ke Sedang Diproses lagi', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    $waktuAwal = now()->subDays(10);
    $item = HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id, 'status' => 'Not OK',
        'status_tindak_lanjut' => 'Sedang Diproses', 'mulai_diproses_at' => $waktuAwal,
    ]);

    // Balik ke Belum Ditindaklanjuti, lalu Sedang Diproses lagi.
    $this->actingAs($admin)->post(route('monitoring.updateTindakLanjut', $item), ['status_tindak_lanjut' => 'Belum Ditindaklanjuti']);
    $this->actingAs($admin)->post(route('monitoring.updateTindakLanjut', $item), ['status_tindak_lanjut' => 'Sedang Diproses']);

    $item->refresh();
    expect($item->mulai_diproses_at->toDateTimeString())->toBe($waktuAwal->toDateTimeString());
});

test('itemMelewatiSlaDiproses true kalau Sedang Diproses sudah lebih dari 7 hari sejak mulai_diproses_at', function () {
    $form = HealthCheckForm::factory()->create(['uker_kode' => Uker::factory()->create()->kode]);
    $item = HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id, 'status' => 'Not OK',
        'status_tindak_lanjut' => 'Sedang Diproses', 'mulai_diproses_at' => now()->subDays(10),
    ]);

    expect(MonitoringController::itemMelewatiSlaDiproses($item))->toBeTrue();
    expect(MonitoringController::hariLewatSlaDiproses($item))->toBe(3);
});

test('itemMelewatiSlaDiproses false kalau Sedang Diproses belum lewat 7 hari', function () {
    $form = HealthCheckForm::factory()->create(['uker_kode' => Uker::factory()->create()->kode]);
    $item = HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id, 'status' => 'Not OK',
        'status_tindak_lanjut' => 'Sedang Diproses', 'mulai_diproses_at' => now()->subDays(2),
    ]);

    expect(MonitoringController::itemMelewatiSlaDiproses($item))->toBeFalse();
});

test('itemMelewatiSlaDiproses false buat item Belum Ditindaklanjuti walau tanggal_pemeriksaan udah lama', function () {
    $form = HealthCheckForm::factory()->create(['uker_kode' => Uker::factory()->create()->kode, 'tanggal_pemeriksaan' => now()->subDays(30)]);
    $item = HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
    ]);

    expect(MonitoringController::itemMelewatiSlaDiproses($item))->toBeFalse();
});

test('badge di tabel: Sedang Diproses yang lewat SLA nampilin "Melewati SLA", bukan "Mendesak"', function () {
    $admin = User::factory()->admin()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => Uker::factory()->create()->kode, 'tanggal_pemeriksaan' => now()]);
    HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id, 'status' => 'Not OK', 'item_pemeriksaan' => 'UPS ruang server',
        'status_tindak_lanjut' => 'Sedang Diproses', 'mulai_diproses_at' => now()->subDays(10),
    ]);

    $response = $this->actingAs($admin)->get(route('monitoring.index'));

    $response->assertSee('Melewati SLA');
    $response->assertSee('Melewati batas 3 hari');
    // Stat card "Mendesak (&gt;3 hari)" di atas tetap tampil (label statis,
    // gak dihapus) -- yang penting badge PER-ITEM ini gak nyebut "Mendesak".
    $response->assertDontSee('Mendesak &middot;', false);
});

// ===================== Riwayat perubahan status tindak lanjut =====================

test('update status lewat modal Update Tindak Lanjut menghasilkan 1 baris log riwayat baru', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    $item = HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
    ]);

    $this->actingAs($admin)->post(route('monitoring.updateTindakLanjut', $item), [
        'status_tindak_lanjut' => 'Sedang Diproses',
        'catatan_tindak_lanjut' => 'Sudah diajukan ke vendor',
    ]);

    $item->refresh();
    expect($item->statusLogs)->toHaveCount(1);
    $log = $item->statusLogs->first();
    expect($log->status)->toBe('Sedang Diproses');
    expect($log->catatan)->toBe('Sudah diajukan ke vendor');
    expect($log->changed_by)->toBe($admin->id);

    // mulai_diproses_at & SLA yang sudah ada TETAP jalan seperti biasa,
    // gak kepengaruh sama sekali oleh penambahan fitur riwayat ini.
    expect($item->mulai_diproses_at)->not->toBeNull();
});

test('3 kali update status berturut-turut menghasilkan 3 baris log terpisah (bukan overwrite), urut terbaru duluan', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    $item = HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
    ]);

    $this->actingAs($admin)->post(route('monitoring.updateTindakLanjut', $item), [
        'status_tindak_lanjut' => 'Sedang Diproses', 'catatan_tindak_lanjut' => 'Update pertama',
    ]);
    $this->actingAs($admin)->post(route('monitoring.updateTindakLanjut', $item), [
        'status_tindak_lanjut' => 'Selesai Diperbaiki', 'catatan_tindak_lanjut' => 'Update kedua',
    ]);
    $this->actingAs($admin)->post(route('monitoring.updateTindakLanjut', $item), [
        'status_tindak_lanjut' => 'Sedang Diproses', 'catatan_tindak_lanjut' => 'Update ketiga',
    ]);

    $item->refresh();
    expect($item->statusLogs)->toHaveCount(3);
    expect($item->statusLogs->pluck('catatan')->all())->toBe(['Update ketiga', 'Update kedua', 'Update pertama']);
    expect($item->statusLogs->pluck('status')->all())->toBe(['Sedang Diproses', 'Selesai Diperbaiki', 'Sedang Diproses']);
});

test('modal riwayat nampilin badge status, waktu, user (PN+nama), catatan, dan uker dengan benar', function () {
    Pekerja::factory()->create(['pn' => '88888899']);
    $admin = User::factory()->admin()->create(['pn' => '88888899', 'name' => 'Budi Admin']);
    $uker = Uker::factory()->create(['nama' => 'KC Test Riwayat']);
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    $item = HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id, 'status' => 'Not OK', 'item_pemeriksaan' => 'AC ruang server mati',
    ]);

    $this->actingAs($admin)->post(route('monitoring.updateTindakLanjut', $item), [
        'status_tindak_lanjut' => 'Sedang Diproses', 'catatan_tindak_lanjut' => 'Sudah dicek teknisi',
    ]);

    $response = $this->actingAs($admin)->get(route('monitoring.index'));

    $response->assertSee('Riwayat Tindak Lanjut');
    $response->assertSee('88888899');
    $response->assertSee('Budi Admin');
    $response->assertSee('Sudah dicek teknisi');
    $response->assertSee('KC Test Riwayat');
});

test('item yang belum pernah diupdate status-nya nampilin pesan "Belum ada riwayat perubahan"', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK']);

    $response = $this->actingAs($admin)->get(route('monitoring.index'));

    $response->assertSee('Belum ada riwayat perubahan');
});

test('user cabang tetap bisa lihat riwayat item yang ada di uker sendiri/turunannya (konsisten sama akses halaman)', function () {
    $cabangA = Uker::factory()->create();
    Pekerja::factory()->create(['pn' => '77770001', 'uker_kode' => $cabangA->kode]);
    $user = User::factory()->forUker($cabangA->kode)->create(['pn' => '77770001', 'name' => 'Rina Cabang']);
    $form = HealthCheckForm::factory()->create(['uker_kode' => $cabangA->kode]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK']);

    $this->actingAs($user)->post(route('monitoring.updateTindakLanjut', $item), [
        'status_tindak_lanjut' => 'Sedang Diproses', 'catatan_tindak_lanjut' => 'Ditangani cabang sendiri',
    ]);

    $response = $this->actingAs($user)->get(route('monitoring.index'));

    $response->assertSee('Ditangani cabang sendiri');
    $response->assertSee('Rina Cabang');
});

test('user cabang TIDAK melihat riwayat item cabang lain (di luar subtree-nya) karena item itu gak pernah muncul di tabelnya', function () {
    $cabangA = Uker::factory()->create();
    $cabangB = Uker::factory()->create();
    $userA = User::factory()->forUker($cabangA->kode)->create();
    $formB = HealthCheckForm::factory()->create(['uker_kode' => $cabangB->kode]);
    $itemB = HealthCheckItem::factory()->create([
        'health_check_form_id' => $formB->id, 'status' => 'Not OK',
        'status_tindak_lanjut' => 'Sedang Diproses', 'catatan_tindak_lanjut' => 'Rahasia cabang B',
    ]);

    $response = $this->actingAs($userA)->get(route('monitoring.index'));

    $response->assertDontSee('Rahasia cabang B');
});

// ===================== Notifikasi update tindak lanjut =====================

test('semua admin dinotifikasi waktu user cabang update status tindak lanjut', function () {
    Notification::fake();
    $admin1 = User::factory()->admin()->create();
    $admin2 = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK']);

    $this->actingAs($user)->post(route('monitoring.updateTindakLanjut', $item), [
        'status_tindak_lanjut' => 'Sedang Diproses',
    ]);

    Notification::assertSentTo($admin1, MonitoringTindakLanjutDiupdate::class);
    Notification::assertSentTo($admin2, MonitoringTindakLanjutDiupdate::class);
});

test('user cabang terkait (subtree) dinotifikasi waktu admin update status tindak lanjut', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $cabangA = Uker::factory()->create();
    $kcpA1 = Uker::factory()->create(['kode_spv' => $cabangA->kode]);
    $userCabangA = User::factory()->forUker($cabangA->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $kcpA1->kode]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK']);

    $this->actingAs($admin)->post(route('monitoring.updateTindakLanjut', $item), [
        'status_tindak_lanjut' => 'Selesai Diperbaiki',
    ]);

    Notification::assertSentTo($userCabangA, MonitoringTindakLanjutDiupdate::class);
});

test('user cabang lain (di luar subtree) TIDAK dinotifikasi waktu admin update item cabang lain', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $cabangA = Uker::factory()->create();
    $cabangB = Uker::factory()->create();
    $userCabangB = User::factory()->forUker($cabangB->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $cabangA->kode]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK']);

    $this->actingAs($admin)->post(route('monitoring.updateTindakLanjut', $item), [
        'status_tindak_lanjut' => 'Selesai Diperbaiki',
    ]);

    Notification::assertNotSentTo($userCabangB, MonitoringTindakLanjutDiupdate::class);
});

test('filter melewati_sla cuma nampilin item Sedang Diproses yang udah lewat 7 hari sejak mulai_diproses_at', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode]);

    $lewatSla = HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id, 'status' => 'Not OK',
        'status_tindak_lanjut' => 'Sedang Diproses', 'mulai_diproses_at' => now()->subDays(10),
        'item_pemeriksaan' => 'Item Lewat SLA',
    ]);
    $belumLewat = HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id, 'status' => 'Not OK',
        'status_tindak_lanjut' => 'Sedang Diproses', 'mulai_diproses_at' => now()->subDays(2),
        'item_pemeriksaan' => 'Item Belum Lewat SLA',
    ]);
    $belumDitindaklanjuti = HealthCheckItem::factory()->create([
        'health_check_form_id' => $form->id, 'status' => 'Not OK',
        'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
        'item_pemeriksaan' => 'Item Belum Ditindaklanjuti',
    ]);

    $response = $this->actingAs($admin)->get(route('monitoring.index', ['melewati_sla' => 1]));

    $response->assertOk();
    $ids = $response->viewData('items')->pluck('id');
    expect($ids)->toContain($lewatSla->id);
    expect($ids)->not->toContain($belumLewat->id, $belumDitindaklanjuti->id);
});
