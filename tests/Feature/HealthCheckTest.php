<?php

use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\Uker;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Pola sama kayak buatFileXlsx() di AsetTest.php -- xlsx SUNGGUHAN di disk,
// bukan UploadedFile::fake() yang isinya random bytes, karena
// bulkUpload()/bulkDelete() beneran parse isinya pakai PhpSpreadsheet.
function buatFileXlsxHc(array $rows, string $namaFile = 'upload.xlsx'): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

    $path = tempnam(sys_get_temp_dir(), 'test').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, $namaFile, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

test('guest tidak bisa akses daftar health check', function () {
    $this->get(route('healthcheck.index'))->assertRedirect(route('login'));
});

test('store form health check otomatis generate item checklist sesuai config', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $totalItemSeharusnya = collect(config('health_check_checklist'))->flatten()->count();

    $response = $this->actingAs($user)->post(route('healthcheck.store'), [
        'uker_kode' => $uker->kode,
        'tanggal_pemeriksaan' => now()->toDateString(),
        'periode' => 'Triwulan I 2026',
    ]);

    $form = HealthCheckForm::first();
    $response->assertRedirect(route('healthcheck.edit', $form));
    expect($form->items()->count())->toBe($totalItemSeharusnya);
    expect($form->items()->where('status', 'Belum Diperiksa')->count())->toBe($totalItemSeharusnya);
});

test('form health check baru otomatis ikut generate item checklist kategori F - Genset', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->post(route('healthcheck.store'), [
        'uker_kode' => $uker->kode,
        'tanggal_pemeriksaan' => now()->toDateString(),
        'periode' => 'Triwulan I 2026',
    ]);

    $form = HealthCheckForm::first();
    $itemGenset = $form->items()->where('kategori', 'F - Genset')->get();

    expect($itemGenset->count())->toBe(count(config('health_check_checklist')['F - Genset']));
    expect($itemGenset->pluck('status')->unique()->all())->toBe(['Belum Diperiksa']);
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

test('item checklist bisa dilampirin foto bukti kondisi fisik', function () {
    Storage::fake('public');

    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create([
        'uker_kode' => $uker->kode,
        'tanggal_pemeriksaan' => now()->toDateString(),
    ]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id]);
    $foto = UploadedFile::fake()->image('rak-server.jpg');

    $response = $this->actingAs($user)->put(route('healthcheck.update', $form), [
        'items' => [
            ['id' => $item->id, 'status' => 'Not OK', 'catatan' => 'Rak terbuka tanpa pencatatan', 'foto' => $foto],
        ],
        'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
    ]);

    $response->assertRedirect(route('healthcheck.index'));
    $item->refresh();
    expect($item->foto_path)->not->toBeNull();
    Storage::disk('public')->assertExists($item->foto_path);
    expect($item->foto_url)->toContain($item->foto_path);
});

test('ganti foto item checklist ngehapus foto lama dari disk', function () {
    Storage::fake('public');

    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create([
        'uker_kode' => $uker->kode,
        'tanggal_pemeriksaan' => now()->toDateString(),
    ]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id]);

    $this->actingAs($user)->put(route('healthcheck.update', $form), [
        'items' => [['id' => $item->id, 'status' => 'Not OK', 'foto' => UploadedFile::fake()->image('foto-lama.jpg')]],
        'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
    ]);
    $fotoLama = $item->refresh()->foto_path;

    $this->actingAs($user)->put(route('healthcheck.update', $form), [
        'items' => [['id' => $item->id, 'status' => 'Not OK', 'foto' => UploadedFile::fake()->image('foto-baru.jpg')]],
        'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
    ]);
    $item->refresh();

    expect($item->foto_path)->not->toBe($fotoLama);
    Storage::disk('public')->assertMissing($fotoLama);
    Storage::disk('public')->assertExists($item->foto_path);
});

test('item Not OK dengan foto muncul di halaman Monitoring Kendala', function () {
    Storage::fake('public');

    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create([
        'uker_kode' => $uker->kode,
        'tanggal_pemeriksaan' => now()->toDateString(),
    ]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id]);

    $this->actingAs($user)->put(route('healthcheck.update', $form), [
        'items' => [['id' => $item->id, 'status' => 'Not OK', 'foto' => UploadedFile::fake()->image('bukti.jpg')]],
        'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
    ]);

    $response = $this->actingAs($user)->get(route('monitoring.index'));

    $response->assertOk();
    $response->assertSee($item->fresh()->foto_url, false);
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

// ===================== Reject approval =====================

test('admin bisa menolak form yang menunggu approval, wajib isi catatan', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'status_approval' => 'Menunggu Approval']);

    $gagalTanpaCatatan = $this->actingAs($admin)->post(route('healthcheck.reject', $form), []);
    $gagalTanpaCatatan->assertSessionHasErrors('catatan_approval');

    $response = $this->actingAs($admin)->post(route('healthcheck.reject', $form), [
        'catatan_approval' => 'Data belum lengkap, tolong cek ulang kategori C',
    ]);

    $response->assertRedirect(route('healthcheck.index'));
    $form->refresh();
    expect($form->status_approval)->toBe('Ditolak');
    expect($form->catatan_approval)->toBe('Data belum lengkap, tolong cek ulang kategori C');
    expect($form->approved_by_pn)->toBe($admin->pn);
});

test('user biasa tidak bisa menolak form health check', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'status_approval' => 'Menunggu Approval']);

    $response = $this->actingAs($user)->post(route('healthcheck.reject', $form), ['catatan_approval' => 'Alasan']);

    $response->assertForbidden();
    expect($form->fresh()->status_approval)->toBe('Menunggu Approval');
});

test('form yang belum berstatus Menunggu Approval tidak bisa ditolak', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'status_approval' => 'Draft']);

    $response = $this->actingAs($admin)->post(route('healthcheck.reject', $form), ['catatan_approval' => 'Alasan']);

    $response->assertForbidden();
});

// ===================== Export =====================

test('guest tidak bisa akses export health check', function () {
    $this->get(route('healthcheck.export.excel'))->assertRedirect(route('login'));
    $this->get(route('healthcheck.export.pdf'))->assertRedirect(route('login'));
});

test('user bisa export health check Excel & PDF', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'periode' => 'Agustus 2026']);

    $this->actingAs($user)->get(route('healthcheck.export.excel'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($user)->get(route('healthcheck.export.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

// ===================== Bulk upload/delete & template =====================

test('guest tidak bisa akses fitur bulk upload/delete/template health check', function () {
    $this->get(route('healthcheck.bulkUploadForm'))->assertRedirect(route('login'));
    $this->get(route('healthcheck.downloadTemplate'))->assertRedirect(route('login'));
    $this->get(route('healthcheck.bulkDeleteForm'))->assertRedirect(route('login'));
});

test('admin bisa lihat form bulk upload & bulk delete health check', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('healthcheck.bulkUploadForm'))->assertOk();
    $this->actingAs($admin)->get(route('healthcheck.bulkDeleteForm'))->assertOk();
});

test('download template health check menghasilkan file xlsx', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('healthcheck.downloadTemplate'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('bulk upload health check berhasil membuat form beserta seluruh item checklist-nya', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $totalItemSeharusnya = collect(config('health_check_checklist'))->flatten()->count();

    $file = buatFileXlsxHc([
        ['uker_kode', 'tanggal_pemeriksaan', 'periode', 'pic_pn'],
        [$uker->kode, '2026-08-10', 'Agustus 2026 - Bulk', ''],
    ]);

    $response = $this->actingAs($admin)->post(route('healthcheck.bulkUpload'), ['file' => $file]);

    $response->assertRedirect();
    $form = HealthCheckForm::where('periode', 'Agustus 2026 - Bulk')->first();
    expect($form)->not->toBeNull();
    expect($form->items()->count())->toBe($totalItemSeharusnya);
});

test('bulk upload health check ditolak kalau header file gak sesuai template', function () {
    $admin = User::factory()->admin()->create();
    $file = buatFileXlsxHc([['kolom_salah', 'lainnya']]);

    $response = $this->actingAs($admin)->post(route('healthcheck.bulkUpload'), ['file' => $file]);

    $response->assertSessionHas('formatSalah', true);
});

test('bulk upload health check: user biasa gak bisa upload ke uker di luar subtree-nya', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();

    $file = buatFileXlsxHc([
        ['uker_kode', 'tanggal_pemeriksaan', 'periode', 'pic_pn'],
        [$ukerLain->kode, '2026-08-10', 'Agustus 2026 - Ditolak', ''],
    ]);

    $response = $this->actingAs($user)->post(route('healthcheck.bulkUpload'), ['file' => $file]);

    $response->assertSessionHas('gagal');
    expect(HealthCheckForm::where('periode', 'Agustus 2026 - Ditolak')->exists())->toBeFalse();
});

test('bulk delete health check menghapus form berdasarkan uker+periode', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'periode' => 'Agustus 2026 - Hapus']);

    $file = buatFileXlsxHc([['uker_kode', 'periode'], [$uker->kode, 'Agustus 2026 - Hapus']]);

    $response = $this->actingAs($admin)->post(route('healthcheck.bulkDelete'), ['file' => $file]);

    $response->assertRedirect();
    expect(HealthCheckForm::where('id', $form->id)->exists())->toBeFalse();
    expect(HealthCheckForm::onlyTrashed()->where('id', $form->id)->exists())->toBeTrue();
});

test('daftar health check bisa diurutkan lewat klik header tabel (misal by tanggal)', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'periode' => 'A', 'tanggal_pemeriksaan' => '2026-08-10']);
    HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'periode' => 'B', 'tanggal_pemeriksaan' => '2026-01-05']);

    $response = $this->actingAs($admin)->get(route('healthcheck.index', ['sort' => 'tanggal_pemeriksaan', 'dir' => 'asc']));

    $urutan = $response->viewData('formList')->pluck('periode')->values();
    expect($urutan->first())->toBe('B');
    expect($urutan->last())->toBe('A');
});

test('sort field yang gak ada di whitelist buat health check diabaikan, gak bikin error', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('healthcheck.index', ['sort' => 'catatan_approval', 'dir' => 'asc']));

    $response->assertOk();
});

// ===================== QR Ruangan =====================

test('scan QR ruangan langsung buka form editable yang ada, ke tab kategori yang sesuai', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create([
        'uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->toDateString(), 'status_approval' => 'Draft',
    ]);

    $response = $this->actingAs($admin)->get(route('healthcheck.scanRuangan', [
        'uker_kode' => $uker->kode, 'kategori' => 'B - CCTV & Storage',
    ]));

    $response->assertRedirect(route('healthcheck.edit', ['healthcheck' => $form->id, 'kategori' => 'B - CCTV & Storage']));
});

test('scan QR ruangan diarahkan ke buat form baru kalau belum ada form yang bisa diedit', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();

    $response = $this->actingAs($admin)->get(route('healthcheck.scanRuangan', [
        'uker_kode' => $uker->kode, 'kategori' => 'A - Ruang Server/Jaringan',
    ]));

    $response->assertRedirect(route('healthcheck.create', [
        'uker_kode' => $uker->kode, 'kategori_tujuan' => 'A - Ruang Server/Jaringan',
    ]));
});

test('scan QR ruangan diarahkan bikin form baru kalau form yang ada udah terkunci tanggal', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    HealthCheckForm::factory()->create([
        'uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->subDay(), 'status_approval' => 'Draft',
    ]);

    $response = $this->actingAs($admin)->get(route('healthcheck.scanRuangan', [
        'uker_kode' => $uker->kode, 'kategori' => 'A - Ruang Server/Jaringan',
    ]));

    $response->assertRedirect(route('healthcheck.create', [
        'uker_kode' => $uker->kode, 'kategori_tujuan' => 'A - Ruang Server/Jaringan',
    ]));
});

test('scan QR ruangan ditolak kalau uker di luar wewenang', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();

    $this->actingAs($user)->get(route('healthcheck.scanRuangan', [
        'uker_kode' => $ukerLain->kode, 'kategori' => 'A - Ruang Server/Jaringan',
    ]))->assertForbidden();
});

test('form health check baru dari kategori_tujuan otomatis diarahkan ke tab yang benar', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $response = $this->actingAs($user)->post(route('healthcheck.store'), [
        'uker_kode' => $uker->kode,
        'tanggal_pemeriksaan' => now()->toDateString(),
        'periode' => 'Minggu Ini',
        'kategori_tujuan' => 'C - Jaringan',
    ]);

    $form = HealthCheckForm::where('uker_kode', $uker->kode)->first();
    $response->assertRedirect(route('healthcheck.edit', ['healthcheck' => $form->id, 'kategori' => 'C - Jaringan']));
});

test('halaman edit health check buka tab sesuai query kategori', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->toDateString()]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'kategori' => 'A - Ruang Server/Jaringan']);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'kategori' => 'C - Jaringan']);

    // Kategori "C - Jaringan" adalah index ke-2 (A=0, B=1(gak ada di form ini), ...)
    // -- di form ini cuma ada A & C, jadi urutan grouping-nya A=0, C=1.
    $response = $this->actingAs($admin)->get(route('healthcheck.edit', ['healthcheck' => $form->id, 'kategori' => 'C - Jaringan']));

    $response->assertOk();
    $response->assertSee('tab: 1', false);
});

test('generate gambar QR ruangan menghasilkan PNG', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();

    $response = $this->actingAs($admin)->get(route('healthcheck.qrRuanganImage', [
        'uker_kode' => $uker->kode, 'kategori' => 'A - Ruang Server/Jaringan',
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/png');
});

test('halaman cetak QR ruangan nampilin uker sesuai KODE yang dipilih, bukan id', function () {
    $admin = User::factory()->admin()->create();
    // Bikin uker lain duluan biar id & kode-nya jelas beda (regresi bug:
    // sebelumnya pakai findOrFail() yang nyari lewat id, bukan where('kode').
    Uker::factory()->create(['nama' => 'KC Lainnya']);
    $ukerTarget = Uker::factory()->create(['kode' => 12345, 'nama' => 'KC Target Benar']);

    $response = $this->actingAs($admin)->get(route('healthcheck.qrRuanganCetak', [
        'uker_kode' => $ukerTarget->kode, 'kategori' => 'A - Ruang Server/Jaringan',
    ]));

    $response->assertOk();
    $response->assertSee('KC Target Benar');
    $response->assertDontSee('KC Lainnya');
});
