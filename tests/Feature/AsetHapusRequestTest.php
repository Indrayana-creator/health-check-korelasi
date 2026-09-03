<?php

use App\Models\Aset;
use App\Models\AsetHapusRequest;
use App\Models\KodeAset;
use App\Models\Uker;
use App\Models\User;

test('guest tidak bisa akses permintaan hapus aset', function () {
    $this->get(route('aset.hapusRequests.index'))->assertRedirect(route('login'));
});

test('user biasa tidak bisa akses daftar maupun approve/reject permintaan hapus', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $hapusRequest = AsetHapusRequest::create([
        'aset_id' => $aset->id,
        'requested_by' => $user->id,
        'status' => 'Menunggu',
    ]);

    $this->actingAs($user)->get(route('aset.hapusRequests.index'))->assertForbidden();
    $this->actingAs($user)->post(route('aset.hapusRequests.approve', $hapusRequest))->assertForbidden();
    $this->actingAs($user)->post(route('aset.hapusRequests.reject', $hapusRequest), ['catatan_admin' => 'Alasan'])->assertForbidden();
});

test('admin melihat daftar permintaan hapus dengan yang menunggu tampil lebih dulu', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $sudahDisetujui = AsetHapusRequest::create([
        'aset_id' => $aset->id, 'requested_by' => $user->id, 'status' => 'Disetujui',
    ]);
    $menunggu = AsetHapusRequest::create([
        'aset_id' => $aset->id, 'requested_by' => $user->id, 'status' => 'Menunggu',
    ]);

    $response = $this->actingAs($admin)->get(route('aset.hapusRequests.index'));

    $response->assertOk();
    $requests = $response->viewData('requests');
    expect($requests->total())->toBe(2);
    expect($requests->first()->id)->toBe($menunggu->id);
});

test('admin menolak permintaan hapus wajib mengisi catatan alasan', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $hapusRequest = AsetHapusRequest::create(['aset_id' => $aset->id, 'requested_by' => $user->id, 'status' => 'Menunggu']);

    $response = $this->actingAs($admin)->post(route('aset.hapusRequests.reject', $hapusRequest), []);

    $response->assertSessionHasErrors('catatan_admin');
    expect($hapusRequest->fresh()->status)->toBe('Menunggu');
});

test('admin menolak permintaan hapus menyimpan catatan dan aset tetap ada', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $hapusRequest = AsetHapusRequest::create(['aset_id' => $aset->id, 'requested_by' => $user->id, 'status' => 'Menunggu']);

    $response = $this->actingAs($admin)->post(route('aset.hapusRequests.reject', $hapusRequest), [
        'catatan_admin' => 'Masih dipakai unit',
    ]);

    $response->assertRedirect();
    $hapusRequest->refresh();
    expect($hapusRequest->status)->toBe('Ditolak');
    expect($hapusRequest->catatan_admin)->toBe('Masih dipakai unit');
    expect($hapusRequest->handled_by)->toBe($admin->id);
    expect($hapusRequest->handled_at)->not->toBeNull();

    $this->actingAs($user)->delete(route('aset.destroy', $aset))->assertForbidden();
    expect(Aset::find($aset->id))->not->toBeNull();
});

test('admin menyetujui permintaan hapus mencatat handled_by dan handled_at', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $hapusRequest = AsetHapusRequest::create(['aset_id' => $aset->id, 'requested_by' => $user->id, 'status' => 'Menunggu']);

    $response = $this->actingAs($admin)->post(route('aset.hapusRequests.approve', $hapusRequest));

    $response->assertRedirect();
    $hapusRequest->refresh();
    expect($hapusRequest->status)->toBe('Disetujui');
    expect($hapusRequest->handled_by)->toBe($admin->id);
    expect($hapusRequest->handled_at)->not->toBeNull();
});

test('user biasa tidak bisa export permintaan hapus aset', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('aset.hapusRequests.export.excel'))->assertForbidden();
    $this->actingAs($user)->get(route('aset.hapusRequests.export.pdf'))->assertForbidden();
});

test('admin bisa export permintaan hapus aset Excel & PDF', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    AsetHapusRequest::create(['aset_id' => $aset->id, 'requested_by' => $user->id, 'status' => 'Menunggu']);

    $this->actingAs($admin)->get(route('aset.hapusRequests.export.excel'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($admin)->get(route('aset.hapusRequests.export.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('user gak bisa ngajuin permintaan hapus buat aset uker lain', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();
    $aset = Aset::factory()->create(['uker_kode' => $ukerLain->kode]);

    $this->actingAs($user)->post(route('aset.requestDelete', $aset), ['alasan' => 'Rusak'])->assertForbidden();
    expect(AsetHapusRequest::count())->toBe(0);
});
