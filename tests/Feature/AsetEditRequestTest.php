<?php

use App\Models\Aset;
use App\Models\AsetEditRequest;
use App\Models\KodeAset;
use App\Models\Uker;
use App\Models\User;

test('guest tidak bisa akses permintaan edit aset', function () {
    $this->get(route('aset.editRequests.index'))->assertRedirect(route('login'));
});

test('user biasa tidak bisa akses daftar maupun approve/reject permintaan edit', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $editRequest = AsetEditRequest::create([
        'aset_id' => $aset->id,
        'requested_by' => $user->id,
        'status' => 'Menunggu',
    ]);

    $this->actingAs($user)->get(route('aset.editRequests.index'))->assertForbidden();
    $this->actingAs($user)->post(route('aset.editRequests.approve', $editRequest))->assertForbidden();
    $this->actingAs($user)->post(route('aset.editRequests.reject', $editRequest), ['catatan_admin' => 'Alasan'])->assertForbidden();
});

test('admin melihat daftar permintaan edit dengan yang menunggu tampil lebih dulu', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $sudahDisetujui = AsetEditRequest::create([
        'aset_id' => $aset->id, 'requested_by' => $user->id, 'status' => 'Disetujui',
    ]);
    $menunggu = AsetEditRequest::create([
        'aset_id' => $aset->id, 'requested_by' => $user->id, 'status' => 'Menunggu',
    ]);

    $response = $this->actingAs($admin)->get(route('aset.editRequests.index'));

    $response->assertOk();
    $requests = $response->viewData('requests');
    expect($requests->total())->toBe(2);
    expect($requests->first()->id)->toBe($menunggu->id);
});

test('admin menolak permintaan edit wajib mengisi catatan alasan', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $editRequest = AsetEditRequest::create(['aset_id' => $aset->id, 'requested_by' => $user->id, 'status' => 'Menunggu']);

    $response = $this->actingAs($admin)->post(route('aset.editRequests.reject', $editRequest), []);

    $response->assertSessionHasErrors('catatan_admin');
    expect($editRequest->fresh()->status)->toBe('Menunggu');
});

test('admin menolak permintaan edit menyimpan catatan dan tetap mengunci aset', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $editRequest = AsetEditRequest::create(['aset_id' => $aset->id, 'requested_by' => $user->id, 'status' => 'Menunggu']);

    $response = $this->actingAs($admin)->post(route('aset.editRequests.reject', $editRequest), [
        'catatan_admin' => 'SN yang diajukan tidak valid',
    ]);

    $response->assertRedirect();
    $editRequest->refresh();
    expect($editRequest->status)->toBe('Ditolak');
    expect($editRequest->catatan_admin)->toBe('SN yang diajukan tidak valid');
    expect($editRequest->handled_by)->toBe($admin->id);
    expect($editRequest->handled_at)->not->toBeNull();

    // Permintaan ditolak -> aset tetap terkunci, user gak bisa update
    $response = $this->actingAs($user)->put(route('aset.update', $aset), [
        'uker_kode' => $uker->kode,
        'kode_aset_kode' => $kodeAset->kode,
        'merek' => 'Merek Baru',
        'tipe_model' => $aset->tipe_model,
        'sn' => $aset->sn,
    ]);
    $response->assertForbidden();
});

test('admin menyetujui permintaan edit mencatat handled_by dan handled_at', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $editRequest = AsetEditRequest::create(['aset_id' => $aset->id, 'requested_by' => $user->id, 'status' => 'Menunggu']);

    $response = $this->actingAs($admin)->post(route('aset.editRequests.approve', $editRequest));

    $response->assertRedirect();
    $editRequest->refresh();
    expect($editRequest->status)->toBe('Disetujui');
    expect($editRequest->handled_by)->toBe($admin->id);
    expect($editRequest->handled_at)->not->toBeNull();
});

test('user biasa tidak bisa export permintaan edit aset', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('aset.editRequests.export.excel'))->assertForbidden();
    $this->actingAs($user)->get(route('aset.editRequests.export.pdf'))->assertForbidden();
});

test('admin bisa export permintaan edit aset Excel & PDF', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    AsetEditRequest::create(['aset_id' => $aset->id, 'requested_by' => $user->id, 'status' => 'Menunggu']);

    $this->actingAs($admin)->get(route('aset.editRequests.export.excel'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($admin)->get(route('aset.editRequests.export.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
