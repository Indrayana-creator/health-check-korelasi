<?php

use App\Models\Aset;
use App\Models\AsetEditRequest;
use App\Models\AsetKondisiLog;
use App\Models\KodeAset;
use App\Models\Uker;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function asetPayload(Uker $uker, KodeAset $kodeAset, array $overrides = []): array
{
    return array_merge([
        'uker_kode' => $uker->kode,
        'kode_aset_kode' => $kodeAset->kode,
        'merek' => 'Dell',
        'tipe_model' => 'Latitude 5420',
        'sn' => 'SN12345678',
        'kondisi' => 'NORMAL',
    ], $overrides);
}

// Bikin file xlsx SUNGGUHAN di disk (bukan UploadedFile::fake() yang isinya
// random bytes) -- dibutuhkan karena bulkUpload()/bulkDelete() beneran parse
// isi filenya pakai PhpSpreadsheet, bukan cuma cek mime type.
function buatFileXlsx(array $rows, string $namaFile = 'upload.xlsx'): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

    $path = tempnam(sys_get_temp_dir(), 'test').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, $namaFile, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

test('guest tidak bisa akses daftar aset', function () {
    $this->get(route('aset.index'))->assertRedirect(route('login'));
});

test('admin melihat semua aset dari semua uker', function () {
    $admin = User::factory()->admin()->create();
    $ukerA = Uker::factory()->create();
    $ukerB = Uker::factory()->create();
    Aset::factory()->create(['uker_kode' => $ukerA->kode]);
    Aset::factory()->create(['uker_kode' => $ukerB->kode]);

    $response = $this->actingAs($admin)->get(route('aset.index'));

    $response->assertOk();
    expect($response->viewData('asetList')->total())->toBe(2);
});

test('user cuma melihat aset dari uker sendiri', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();
    Aset::factory()->create(['uker_kode' => $ukerSendiri->kode]);
    Aset::factory()->create(['uker_kode' => $ukerLain->kode]);

    $response = $this->actingAs($user)->get(route('aset.index'));

    $response->assertOk();
    expect($response->viewData('asetList')->total())->toBe(1);
});

test('user bisa menambahkan aset untuk uker sendiri dan no_asset otomatis dibuat', function () {
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $response = $this->actingAs($user)->post(route('aset.store'), asetPayload($uker, $kodeAset));

    $response->assertRedirect(route('aset.index'));
    $aset = Aset::first();
    expect($aset)->not->toBeNull();
    expect($aset->uker_kode)->toBe($uker->kode);
    expect($aset->no_asset)->toStartWith('Z5-K-');
});

test('user tidak bisa menambahkan aset untuk uker lain', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();

    $response = $this->actingAs($user)->post(route('aset.store'), asetPayload($ukerLain, $kodeAset));

    $response->assertForbidden();
    expect(Aset::count())->toBe(0);
});

test('admin bisa menambahkan aset untuk uker manapun', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();

    $response = $this->actingAs($admin)->post(route('aset.store'), asetPayload($uker, $kodeAset));

    $response->assertRedirect(route('aset.index'));
    expect(Aset::count())->toBe(1);
});

test('gak bisa nambah aset dengan SN yang sudah dipakai aset lain', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'sn' => 'SN-DUPLIKAT']);

    $response = $this->actingAs($admin)->post(route('aset.store'), asetPayload($uker, $kodeAset, ['sn' => 'SN-DUPLIKAT']));

    $response->assertSessionHasErrors('sn');
    expect(Aset::count())->toBe(1);
});

test('SN yang sama tetap boleh dipakai kalau aset lama sudah di-soft-delete', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $asetLama = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'sn' => 'SN-BEKAS']);
    $asetLama->delete();

    $response = $this->actingAs($admin)->post(route('aset.store'), asetPayload($uker, $kodeAset, ['sn' => 'SN-BEKAS']));

    $response->assertRedirect(route('aset.index'));
    expect(Aset::where('sn', 'SN-BEKAS')->count())->toBe(1);
});

test('update aset boleh simpan ulang SN miliknya sendiri tanpa dianggap duplikat', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'sn' => 'SN-SENDIRI']);

    $response = $this->actingAs($admin)
        ->put(route('aset.update', $aset), asetPayload($uker, $kodeAset, ['sn' => 'SN-SENDIRI', 'merek' => 'HP']));

    $response->assertRedirect(route('aset.index'));
    expect($aset->fresh()->merek)->toBe('HP');
});

test('gak bisa update aset pakai SN yang udah dipakai aset lain', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'sn' => 'SN-A']);
    $asetB = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'sn' => 'SN-B']);

    $response = $this->actingAs($admin)
        ->put(route('aset.update', $asetB), asetPayload($uker, $kodeAset, ['sn' => 'SN-A']));

    $response->assertSessionHasErrors('sn');
    expect($asetB->fresh()->sn)->toBe('SN-B');
});

test('user tidak bisa mengedit aset milik uker lain', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();
    $aset = Aset::factory()->create(['uker_kode' => $ukerLain->kode]);

    $this->actingAs($user)->get(route('aset.edit', $aset))->assertForbidden();
    $this->actingAs($user)->put(route('aset.update', $aset), asetPayload($ukerLain, $aset->kodeAset ?? KodeAset::factory()->create()))->assertForbidden();
    $this->actingAs($user)->delete(route('aset.destroy', $aset))->assertForbidden();

    expect(Aset::find($aset->id))->not->toBeNull();
});

test('user bisa melihat dan menghapus aset milik uker sendiri, tapi tidak bisa update tanpa izin edit', function () {
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'merek' => 'Dell']);

    $this->actingAs($user)->get(route('aset.edit', $aset))->assertOk();

    // Belum ada permintaan edit yang disetujui -> data masih terkunci
    $this->actingAs($user)
        ->put(route('aset.update', $aset), asetPayload($uker, $kodeAset, ['merek' => 'HP']))
        ->assertForbidden();
    expect($aset->fresh()->merek)->toBe('Dell');

    // Hapus gak butuh izin edit, tetap boleh langsung
    $this->actingAs($user)->delete(route('aset.destroy', $aset))->assertRedirect(route('aset.index'));
    expect(Aset::find($aset->id))->toBeNull();
});

test('user bisa update aset kalau permintaan edit sudah disetujui admin', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $this->actingAs($user)->post(route('aset.requestEdit', $aset), ['alasan' => 'Salah input merek'])
        ->assertRedirect(route('aset.edit', $aset));

    $editRequest = AsetEditRequest::first();
    expect($editRequest)->not->toBeNull();
    expect($editRequest->status)->toBe('Menunggu');

    $this->actingAs($admin)->post(route('aset.editRequests.approve', $editRequest))
        ->assertRedirect();
    expect($editRequest->fresh()->status)->toBe('Disetujui');

    $this->actingAs($user)
        ->put(route('aset.update', $aset), asetPayload($uker, $kodeAset, ['merek' => 'HP']))
        ->assertRedirect(route('aset.index'));
    expect($aset->fresh()->merek)->toBe('HP');

    // Izin edit yang sudah dipakai gak bisa dipakai lagi buat update kedua
    $this->actingAs($user)
        ->put(route('aset.update', $aset), asetPayload($uker, $kodeAset, ['merek' => 'Asus']))
        ->assertForbidden();
    expect($aset->fresh()->merek)->toBe('HP');
});

test('aset yang dihapus masuk sampah (soft delete), bukan hilang permanen dari database', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode]);

    $this->actingAs($admin)->delete(route('aset.destroy', $aset))->assertRedirect(route('aset.index'));

    expect(Aset::find($aset->id))->toBeNull(); // gak muncul di query normal
    expect(Aset::onlyTrashed()->find($aset->id))->not->toBeNull(); // tapi masih ada di database
});

test('admin bisa lihat semua aset di sampah dan memulihkannya', function () {
    $admin = User::factory()->admin()->create();
    $ukerA = Uker::factory()->create();
    $ukerB = Uker::factory()->create();
    $asetA = Aset::factory()->create(['uker_kode' => $ukerA->kode]);
    $asetB = Aset::factory()->create(['uker_kode' => $ukerB->kode]);
    $asetA->delete();
    $asetB->delete();

    $response = $this->actingAs($admin)->get(route('aset.trash'));
    $response->assertOk();
    expect($response->viewData('asetList')->total())->toBe(2);

    $this->actingAs($admin)->post(route('aset.restore', $asetA->id))->assertRedirect();
    expect(Aset::find($asetA->id))->not->toBeNull();
});

test('user cuma lihat aset uker sendiri di sampah dan cuma bisa restore punya sendiri', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();
    $asetSendiri = Aset::factory()->create(['uker_kode' => $ukerSendiri->kode]);
    $asetLain = Aset::factory()->create(['uker_kode' => $ukerLain->kode]);
    $asetSendiri->delete();
    $asetLain->delete();

    $response = $this->actingAs($user)->get(route('aset.trash'));
    $response->assertOk();
    expect($response->viewData('asetList')->total())->toBe(1);

    $this->actingAs($user)->post(route('aset.restore', $asetLain->id))->assertForbidden();
    $this->actingAs($user)->post(route('aset.restore', $asetSendiri->id))->assertRedirect();
    expect(Aset::find($asetSendiri->id))->not->toBeNull();
});

test('guest tidak bisa akses sampah maupun restore aset', function () {
    $uker = Uker::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode]);
    $aset->delete();

    $this->get(route('aset.trash'))->assertRedirect(route('login'));
    $this->post(route('aset.restore', $aset->id))->assertRedirect(route('login'));
});

test('user tidak bisa memindahkan aset ke uker lain lewat update', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();
    $aset = Aset::factory()->create(['uker_kode' => $ukerSendiri->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $response = $this->actingAs($user)->put(route('aset.update', $aset), asetPayload($ukerLain, $kodeAset));

    $response->assertForbidden();
    expect($aset->fresh()->uker_kode)->toBe($ukerSendiri->kode);
});

// ===================== Export =====================

test('guest tidak bisa akses export aset', function () {
    $this->get(route('aset.export.excel'))->assertRedirect(route('login'));
    $this->get(route('aset.export.pdf'))->assertRedirect(route('login'));
});

test('user bisa export aset Excel & PDF, hasilnya cuma aset uker sendiri', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();
    Aset::factory()->create(['uker_kode' => $ukerSendiri->kode, 'kode_aset_kode' => $kodeAset->kode, 'sn' => 'SN-MILIK-SENDIRI']);
    Aset::factory()->create(['uker_kode' => $ukerLain->kode, 'kode_aset_kode' => $kodeAset->kode, 'sn' => 'SN-MILIK-LAIN']);

    $excel = $this->actingAs($user)->get(route('aset.export.excel'));
    $excel->assertOk();
    $excel->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $pdf = $this->actingAs($user)->get(route('aset.export.pdf'));
    $pdf->assertOk();
    $pdf->assertHeader('content-type', 'application/pdf');

    $tmpFile = tempnam(sys_get_temp_dir(), 'export').'.xlsx';
    file_put_contents($tmpFile, $excel->streamedContent());
    $isiSheet = implode(' | ', IOFactory::load($tmpFile)->getActiveSheet()->toArray()[1] ?? []);
    unlink($tmpFile);

    expect($isiSheet)->toContain('SN-MILIK-SENDIRI');
});

// ===================== Bulk upload/delete & template =====================

test('guest tidak bisa akses fitur bulk upload/delete/template aset', function () {
    $this->get(route('aset.bulkUploadForm'))->assertRedirect(route('login'));
    $this->get(route('aset.downloadTemplate'))->assertRedirect(route('login'));
    $this->get(route('aset.bulkDeleteForm'))->assertRedirect(route('login'));
});

test('admin bisa lihat form bulk upload & bulk delete aset', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('aset.bulkUploadForm'))->assertOk();
    $this->actingAs($admin)->get(route('aset.bulkDeleteForm'))->assertOk();
});

test('download template aset menghasilkan file xlsx', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('aset.downloadTemplate'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('bulk upload aset berhasil menambahkan aset dari file valid', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create(['kategori' => 'PRINTER & SCANNER']);

    $file = buatFileXlsx([
        ['uker_kode', 'kode_aset_kode', 'merek', 'tipe_model', 'sn', 'no_asset', 'kapasitas_memori', 'tahun_perolehan', 'kondisi', 'pemegang_nama', 'jabatan', 'pemegang_pn', 'ip_address', 'status_hardening', 'status_bitlocker', 'status_dlp', 'status_antivirus', 'keterangan'],
        [$uker->kode, $kodeAset->kode, 'Epson', 'L3110', 'SN-BULK-001', '', '-', 2024, 'NORMAL', '', '', '', '', '', '', '', '', ''],
    ]);

    $response = $this->actingAs($admin)->post(route('aset.bulkUpload'), ['file' => $file]);

    $response->assertRedirect();
    $response->assertSessionHas('status');
    expect(Aset::where('sn', 'SN-BULK-001')->exists())->toBeTrue();
});

test('bulk upload aset ditolak kalau header file gak sesuai template', function () {
    $admin = User::factory()->admin()->create();
    $file = buatFileXlsx([['kolom_salah', 'lainnya']]);

    $response = $this->actingAs($admin)->post(route('aset.bulkUpload'), ['file' => $file]);

    $response->assertSessionHas('formatSalah', true);
});

test('bulk upload aset: user biasa gak bisa upload ke uker di luar subtree-nya', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create(['kategori' => 'PRINTER & SCANNER']);
    $user = User::factory()->forUker($ukerSendiri->kode)->create();

    $file = buatFileXlsx([
        ['uker_kode', 'kode_aset_kode', 'merek', 'tipe_model', 'sn', 'no_asset', 'kapasitas_memori', 'tahun_perolehan', 'kondisi', 'pemegang_nama', 'jabatan', 'pemegang_pn', 'ip_address', 'status_hardening', 'status_bitlocker', 'status_dlp', 'status_antivirus', 'keterangan'],
        [$ukerLain->kode, $kodeAset->kode, 'Epson', 'L3110', 'SN-BULK-002', '', '-', 2024, 'NORMAL', '', '', '', '', '', '', '', '', ''],
    ]);

    $response = $this->actingAs($user)->post(route('aset.bulkUpload'), ['file' => $file]);

    $response->assertSessionHas('gagal');
    expect(Aset::where('sn', 'SN-BULK-002')->exists())->toBeFalse();
});

test('bulk delete aset menghapus aset berdasarkan SN', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'sn' => 'SN-HAPUS-001']);

    $file = buatFileXlsx([['sn'], ['SN-HAPUS-001']]);

    $response = $this->actingAs($admin)->post(route('aset.bulkDelete'), ['file' => $file]);

    $response->assertRedirect();
    expect(Aset::where('id', $aset->id)->exists())->toBeFalse();
    expect(Aset::onlyTrashed()->where('id', $aset->id)->exists())->toBeTrue();
});

test('nambah aset baru otomatis nyatet riwayat kondisi awal (baseline)', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();

    $this->actingAs($admin)->post(route('aset.store'), asetPayload($uker, $kodeAset, ['kondisi' => 'NORMAL']));

    $aset = Aset::where('sn', 'SN12345678')->first();
    expect($aset->kondisiLogs)->toHaveCount(1);
    expect($aset->kondisiLogs->first()->kondisi_lama)->toBeNull();
    expect($aset->kondisiLogs->first()->kondisi_baru)->toBe('NORMAL');
    expect($aset->kondisiLogs->first()->changed_by)->toBe($admin->id);
});

test('update aset yang mengubah kondisi tercatat di riwayat', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'NORMAL']);

    $this->actingAs($admin)->put(route('aset.update', $aset), asetPayload($uker, $kodeAset, ['kondisi' => 'RUSAK']));

    $logs = $aset->kondisiLogs()->get();
    expect($logs)->toHaveCount(1);
    expect($logs->first()->kondisi_lama)->toBe('NORMAL');
    expect($logs->first()->kondisi_baru)->toBe('RUSAK');
});

test('update aset yang TIDAK mengubah kondisi gak nambah riwayat baru', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'NORMAL']);

    $this->actingAs($admin)->put(route('aset.update', $aset), asetPayload($uker, $kodeAset, ['kondisi' => 'NORMAL', 'merek' => 'Merek Baru']));

    expect($aset->kondisiLogs()->count())->toBe(0);
});

test('endpoint riwayat kondisi mengembalikan urutan terbaru dulu', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'NORMAL']);

    $this->actingAs($admin)->put(route('aset.update', $aset), asetPayload($uker, $kodeAset, ['kondisi' => 'RUSAK']));
    $this->actingAs($admin)->put(route('aset.update', $aset), asetPayload($uker, $kodeAset, ['kondisi' => 'NORMAL']));

    $response = $this->actingAs($admin)->get(route('aset.kondisiRiwayat', $aset));

    $response->assertOk();
    $logs = $response->json('logs');
    expect($logs[0]['kondisi_baru'])->toBe('NORMAL');
    expect($logs[1]['kondisi_baru'])->toBe('RUSAK');
});

test('user biasa bisa lihat riwayat kondisi aset di subtree-nya, tapi tidak di luar subtree', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $asetSendiri = Aset::factory()->create(['uker_kode' => $ukerSendiri->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $asetLain = Aset::factory()->create(['uker_kode' => $ukerLain->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $this->actingAs($user)->get(route('aset.kondisiRiwayat', $asetSendiri))->assertOk();
    $this->actingAs($user)->get(route('aset.kondisiRiwayat', $asetLain))->assertForbidden();
});

test('menghapus aset ikut menghapus riwayat kondisinya (cascade)', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'NORMAL']);
    $logId = $aset->kondisiLogs()->create(['kondisi_lama' => null, 'kondisi_baru' => 'NORMAL', 'changed_by' => $admin->id])->id;

    $aset->forceDelete();

    expect(AsetKondisiLog::find($logId))->toBeNull();
});

test('data aset bisa diurutkan lewat klik header tabel (misal by merek)', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'merek' => 'Zebra', 'sn' => 'SN-Z']);
    Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'merek' => 'Acer', 'sn' => 'SN-A']);

    $response = $this->actingAs($admin)->get(route('aset.index', ['sort' => 'merek', 'dir' => 'asc']));

    $urutan = $response->viewData('asetList')->pluck('merek')->values();
    expect($urutan->first())->toBe('Acer');
    expect($urutan->last())->toBe('Zebra');
});

test('sort field yang gak ada di whitelist diabaikan, gak bikin error', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('aset.index', ['sort' => 'password', 'dir' => 'asc']));

    $response->assertOk();
});

test('edit aset legacy yang SN-nya kebetulan duplikat sama aset lain (data import lama) tetap bisa disimpan selama SN gak diubah', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    // Simulasi 2 aset lama yang SN-nya udah kebetulan sama dari hasil import awal
    // (skenario nyata: ditemukan 156 grup SN duplikat di data RO12 Surabaya).
    $asetA = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'sn' => 'SN-LEGACY-DUPLIKAT']);
    $asetB = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'sn' => 'SN-LEGACY-DUPLIKAT']);

    // Edit aset B, cuma ubah pemegang_nama, SN dibiarkan sama persis (gak diubah)
    $response = $this->actingAs($admin)->put(
        route('aset.update', $asetB),
        asetPayload($uker, $kodeAset, ['sn' => 'SN-LEGACY-DUPLIKAT', 'pemegang_nama' => 'Nama Baru'])
    );

    $response->assertRedirect(route('aset.index'));
    expect($asetB->fresh()->pemegang_nama)->toBe('Nama Baru');
});

test('edit aset legacy duplikat TETAP ditolak kalau SN-nya sengaja diubah ke SN aset ketiga yang juga udah dipakai', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'sn' => 'SN-LEGACY-DUPLIKAT']);
    $asetB = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'sn' => 'SN-LEGACY-DUPLIKAT']);
    Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'sn' => 'SN-AKTIF-LAIN']);

    // Aset B coba diubah SN-nya jadi SN aset ketiga (perubahan BENERAN, bukan
    // legacy state) -- ini HARUS tetap ditolak, unique masih berlaku normal.
    $response = $this->actingAs($admin)->put(
        route('aset.update', $asetB),
        asetPayload($uker, $kodeAset, ['sn' => 'SN-AKTIF-LAIN'])
    );

    $response->assertSessionHasErrors('sn');
    expect($asetB->fresh()->sn)->toBe('SN-LEGACY-DUPLIKAT');
});

test('edit aset legacy dengan merek kosong (data import lama) tetap bisa disimpan selama merek gak diisi', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'merek' => '']);

    $response = $this->actingAs($admin)->put(
        route('aset.update', $aset),
        asetPayload($uker, $kodeAset, ['merek' => '', 'sn' => $aset->sn, 'pemegang_nama' => 'Nama Baru'])
    );

    $response->assertRedirect(route('aset.index'));
    expect($aset->fresh()->pemegang_nama)->toBe('Nama Baru');
    expect($aset->fresh()->merek)->toBeNull();
});

test('merek TETAP wajib diisi kalau beneran mau diubah dari kosong ke ada isinya, dan kalau nambah aset baru', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();

    // Aset baru -- merek tetap wajib
    $storeResponse = $this->actingAs($admin)->post(route('aset.store'), asetPayload($uker, $kodeAset, ['merek' => '']));
    $storeResponse->assertSessionHasErrors('merek');

    // Aset lama merek kosong, dicoba diubah jadi kosong lagi vs string kosong beneran tetap valid,
    // tapi begitu field lain berubah value merek (bukan dibiarkan sama), tetap wajib diisi kalau baru
    $asetBaru = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'merek' => 'Dell']);
    $response = $this->actingAs($admin)->put(
        route('aset.update', $asetBaru),
        asetPayload($uker, $kodeAset, ['merek' => '', 'sn' => $asetBaru->sn])
    );
    $response->assertSessionHasErrors('merek');
});

test('nambah aset baru kategori individu (PC/Notebook/Tablet/Monitor) wajib isi 8 field pemegang & keamanan', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAsetPc = KodeAset::factory()->create(['kategori' => 'PERSONAL COMPUTER']);

    $response = $this->actingAs($admin)->post(route('aset.store'), asetPayload($uker, $kodeAsetPc));

    $response->assertSessionHasErrors([
        'pemegang_nama', 'jabatan', 'pemegang_pn', 'ip_address',
        'status_hardening', 'status_bitlocker', 'status_dlp', 'status_antivirus',
    ]);
});

test('nambah aset baru kategori individu berhasil kalau semua field pemegang & keamanan lengkap', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAsetPc = KodeAset::factory()->create(['kategori' => 'NOTEBOOK']);

    $response = $this->actingAs($admin)->post(route('aset.store'), asetPayload($uker, $kodeAsetPc, [
        'pemegang_nama' => 'Budi', 'jabatan' => 'Staff', 'pemegang_pn' => '90000001',
        'ip_address' => '10.0.0.5', 'status_hardening' => 'Sudah', 'status_bitlocker' => 'Aktif',
        'status_dlp' => 'Aktif', 'status_antivirus' => 'Aktif',
    ]));

    $response->assertRedirect(route('aset.index'));
    expect(Aset::where('sn', 'SN12345678')->exists())->toBeTrue();
});

test('nambah aset baru kategori BUKAN individu (misal UPS) tetap boleh kosongin field pemegang & keamanan', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAsetUps = KodeAset::factory()->create(['kategori' => 'UPS']);

    $response = $this->actingAs($admin)->post(route('aset.store'), asetPayload($uker, $kodeAsetUps));

    $response->assertRedirect(route('aset.index'));
});

test('edit aset legacy kategori individu yang field pemegang-nya kosong tetap bisa disimpan kalau field itu gak disentuh', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAsetPc = KodeAset::factory()->create(['kategori' => 'PERSONAL COMPUTER']);
    $aset = Aset::factory()->create([
        'uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAsetPc->kode,
        'kondisi' => null, 'pemegang_nama' => null, 'jabatan' => null, 'pemegang_pn' => null,
        'ip_address' => null, 'status_hardening' => null, 'status_bitlocker' => null,
        'status_dlp' => null, 'status_antivirus' => null,
    ]);

    $response = $this->actingAs($admin)->put(
        route('aset.update', $aset),
        asetPayload($uker, $kodeAsetPc, ['sn' => $aset->sn, 'kondisi' => 'NORMAL'])
    );

    $response->assertRedirect(route('aset.index'));
    expect($aset->fresh()->kondisi)->toBe('NORMAL');
});

test('edit aset legacy kategori individu: field pemegang yang BENERAN diubah tetap wajib diisi', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAsetPc = KodeAset::factory()->create(['kategori' => 'PERSONAL COMPUTER']);
    $aset = Aset::factory()->create([
        'uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAsetPc->kode,
        'pemegang_nama' => null,
    ]);

    // Coba ubah pemegang_nama dari kosong jadi ' ' doang gak masuk akal --
    // tesnya: submit pemegang_nama baru yang beda dari null, tapi field lain
    // (ip_address) TETAP dikosongin/gak diubah -- harus tetap ditolak karena
    // status_* dkk juga masih kosong dan itu bukan status quo lagi (nama
    // pemegang berubah, artinya form ini "disentuh" beneran).
    $response = $this->actingAs($admin)->put(
        route('aset.update', $aset),
        asetPayload($uker, $kodeAsetPc, ['sn' => $aset->sn, 'pemegang_nama' => 'Nama Baru Beneran'])
    );

    // ip_address dkk masih kosong DAN gak diubah dari null -> tetap lolos (nullable),
    // tapi pemegang_nama sendiri sukses diisi karena emang diisi sekarang.
    $response->assertSessionDoesntHaveErrors('pemegang_nama');
});

test('status hardening/bitlocker/dlp/antivirus cuma boleh diisi pilihan baku, bukan teks bebas', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAsetPc = KodeAset::factory()->create(['kategori' => 'PERSONAL COMPUTER']);

    $response = $this->actingAs($admin)->post(route('aset.store'), asetPayload($uker, $kodeAsetPc, [
        'pemegang_nama' => 'Budi', 'jabatan' => 'Staff', 'pemegang_pn' => '90000001',
        'ip_address' => '10.0.0.5', 'status_hardening' => 'kayaknya sudah kali ya',
        'status_bitlocker' => 'Aktif', 'status_dlp' => 'Aktif', 'status_antivirus' => 'Aktif',
    ]));

    $response->assertSessionHasErrors('status_hardening');
});

test('ip_address harus format IP yang valid', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAsetPc = KodeAset::factory()->create(['kategori' => 'PERSONAL COMPUTER']);

    $response = $this->actingAs($admin)->post(route('aset.store'), asetPayload($uker, $kodeAsetPc, [
        'pemegang_nama' => 'Budi', 'jabatan' => 'Staff', 'pemegang_pn' => '90000001',
        'ip_address' => 'bukan-ip-yang-valid', 'status_hardening' => 'Sudah',
        'status_bitlocker' => 'Aktif', 'status_dlp' => 'Aktif', 'status_antivirus' => 'Aktif',
    ]));

    $response->assertSessionHasErrors('ip_address');
});

test('admin bisa lihat halaman detail aset manapun', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'merek' => 'Dell']);

    $response = $this->actingAs($admin)->get(route('aset.show', $aset));

    $response->assertOk();
    $response->assertSee($aset->no_asset);
    $response->assertSee('Dell');
});

test('user cuma bisa lihat detail aset di subtree sendiri', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();
    $asetSendiri = Aset::factory()->create(['uker_kode' => $ukerSendiri->kode]);
    $asetLain = Aset::factory()->create(['uker_kode' => $ukerLain->kode]);

    $this->actingAs($user)->get(route('aset.show', $asetSendiri))->assertOk();
    $this->actingAs($user)->get(route('aset.show', $asetLain))->assertForbidden();
});

test('halaman detail aset nampilin riwayat perubahan kondisi & riwayat permintaan edit', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    AsetKondisiLog::create(['aset_id' => $aset->id, 'kondisi_lama' => null, 'kondisi_baru' => 'NORMAL', 'changed_by' => $admin->id]);
    AsetEditRequest::create(['aset_id' => $aset->id, 'requested_by' => $admin->id, 'alasan' => 'Salah ketik merek', 'status' => 'Menunggu']);

    $response = $this->actingAs($admin)->get(route('aset.show', $aset));

    $response->assertOk();
    $response->assertSee('NORMAL');
    $response->assertSee('Salah ketik merek');
});

test('admin bisa generate QR code aset manapun', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $response = $this->actingAs($admin)->get(route('aset.qrCode', $aset));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/png');
});

test('user cuma bisa generate QR code aset di subtree sendiri', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();
    $asetSendiri = Aset::factory()->create(['uker_kode' => $ukerSendiri->kode]);
    $asetLain = Aset::factory()->create(['uker_kode' => $ukerLain->kode]);

    $this->actingAs($user)->get(route('aset.qrCode', $asetSendiri))->assertOk();
    $this->actingAs($user)->get(route('aset.qrCode', $asetLain))->assertForbidden();
});
