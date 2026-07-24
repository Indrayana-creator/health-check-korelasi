<?php

use App\Models\Aset;
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

test('user bisa mengelola aset milik uker sendiri', function () {
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $this->actingAs($user)->get(route('aset.edit', $aset))->assertOk();

    $this->actingAs($user)
        ->put(route('aset.update', $aset), asetPayload($uker, $kodeAset, ['merek' => 'HP']))
        ->assertRedirect(route('aset.index'));
    expect($aset->fresh()->merek)->toBe('HP');

    $this->actingAs($user)->delete(route('aset.destroy', $aset))->assertRedirect(route('aset.index'));
    expect(Aset::find($aset->id))->toBeNull();
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
