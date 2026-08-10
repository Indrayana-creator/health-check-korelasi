<?php

use App\Models\Aset;
use App\Models\AsetEditRequest;
use App\Models\KodeAset;
use App\Models\Uker;
use App\Models\User;

function asetPayload(Uker $uker, KodeAset $kodeAset, array $overrides = []): array
{
    return array_merge([
        'uker_kode' => $uker->kode,
        'kode_aset_kode' => $kodeAset->kode,
        'merek' => 'Dell',
        'tipe_model' => 'Latitude 5420',
        'sn' => 'SN12345678',
    ], $overrides);
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
