<?php

use App\Models\Aset;
use App\Models\KodeAset;
use App\Models\Uker;
use App\Models\User;

function kodeAsetPayload(array $overrides = []): array
{
    return array_merge([
        'kode' => 'PC-TEST',
        'kategori' => 'PERSONAL COMPUTER',
        'nama' => 'PC Standar',
    ], $overrides);
}

test('guest tidak bisa akses kelola kode aset', function () {
    $this->get(route('kode-aset.index'))->assertRedirect(route('login'));
});

test('user biasa tidak bisa akses kelola kode aset sama sekali', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();

    $this->actingAs($user)->get(route('kode-aset.index'))->assertForbidden();
    $this->actingAs($user)->get(route('kode-aset.create'))->assertForbidden();
    $this->actingAs($user)->post(route('kode-aset.store'), kodeAsetPayload())->assertForbidden();
    $this->actingAs($user)->get(route('kode-aset.edit', $kodeAset))->assertForbidden();
    $this->actingAs($user)->put(route('kode-aset.update', $kodeAset), kodeAsetPayload())->assertForbidden();
    $this->actingAs($user)->delete(route('kode-aset.destroy', $kodeAset))->assertForbidden();
});

test('admin bisa melihat daftar kode aset dan mencari berdasarkan kode/kategori/nama', function () {
    $admin = User::factory()->admin()->create();
    KodeAset::factory()->create(['kode' => 'NB1', 'kategori' => 'NOTEBOOK', 'nama' => 'Laptop Standar']);
    KodeAset::factory()->create(['kode' => 'PRN1', 'kategori' => 'PRINTER & SCANNER', 'nama' => 'Printer Standar']);

    $response = $this->actingAs($admin)->get(route('kode-aset.index', ['q' => 'Laptop']));

    $response->assertOk();
    expect($response->viewData('kodeAsetList')->total())->toBe(1);
});

test('admin bisa menambah kode aset baru', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('kode-aset.store'), kodeAsetPayload());

    $response->assertRedirect(route('kode-aset.index'));
    expect(KodeAset::where('kode', 'PC-TEST')->exists())->toBeTrue();
});

test('kode aset harus unik saat ditambahkan', function () {
    $admin = User::factory()->admin()->create();
    $sudahAda = KodeAset::factory()->create(['kode' => 'PC-DOBEL']);

    $response = $this->actingAs($admin)->post(route('kode-aset.store'), kodeAsetPayload(['kode' => 'PC-DOBEL']));

    $response->assertSessionHasErrors('kode');
    expect(KodeAset::where('kode', 'PC-DOBEL')->count())->toBe(1);
});

test('admin bisa update kode aset', function () {
    $admin = User::factory()->admin()->create();
    $kodeAset = KodeAset::factory()->create(['kode' => 'PC-UPDATE']);

    $response = $this->actingAs($admin)->put(
        route('kode-aset.update', $kodeAset),
        kodeAsetPayload(['kode' => 'PC-UPDATE', 'nama' => 'PC Sudah Diupdate'])
    );

    $response->assertRedirect(route('kode-aset.index'));
    expect($kodeAset->fresh()->nama)->toBe('PC Sudah Diupdate');
});

test('kode aset dengan kode yang sama saat update tidak dianggap duplikat', function () {
    $admin = User::factory()->admin()->create();
    $kodeAset = KodeAset::factory()->create(['kode' => 'PC-SENDIRI']);

    $response = $this->actingAs($admin)->put(
        route('kode-aset.update', $kodeAset),
        kodeAsetPayload(['kode' => 'PC-SENDIRI'])
    );

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('kode-aset.index'));
});

test('kode aset tidak bisa dihapus kalau masih dipakai aset', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $response = $this->actingAs($admin)->delete(route('kode-aset.destroy', $kodeAset));

    $response->assertRedirect();
    expect(KodeAset::where('kode', $kodeAset->kode)->exists())->toBeTrue();
});

test('kode aset tanpa data aset terkait bisa dihapus', function () {
    $admin = User::factory()->admin()->create();
    $kodeAset = KodeAset::factory()->create();

    $response = $this->actingAs($admin)->delete(route('kode-aset.destroy', $kodeAset));

    $response->assertRedirect(route('kode-aset.index'));
    expect(KodeAset::where('kode', $kodeAset->kode)->exists())->toBeFalse();
});

test('user biasa tidak bisa export kelola kode aset', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('kode-aset.export.excel'))->assertForbidden();
    $this->actingAs($user)->get(route('kode-aset.export.pdf'))->assertForbidden();
});

test('admin bisa export kelola kode aset Excel & PDF', function () {
    $admin = User::factory()->admin()->create();
    KodeAset::factory()->create();

    $this->actingAs($admin)->get(route('kode-aset.export.excel'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($admin)->get(route('kode-aset.export.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
