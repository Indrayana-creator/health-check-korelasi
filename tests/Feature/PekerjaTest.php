<?php

use App\Models\HealthCheckForm;
use App\Models\Pekerja;
use App\Models\Uker;
use App\Models\User;

function pekerjaPayload(array $overrides = []): array
{
    return array_merge([
        'pn' => '90000001',
        'nama' => 'Pekerja Percobaan',
        'jabatan' => 'Staff IT',
    ], $overrides);
}

test('guest tidak bisa akses kelola pekerja', function () {
    $this->get(route('pekerja.index'))->assertRedirect(route('login'));
});

test('user biasa tidak bisa akses kelola pekerja sama sekali', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $pekerja = Pekerja::factory()->create();

    $this->actingAs($user)->get(route('pekerja.index'))->assertForbidden();
    $this->actingAs($user)->get(route('pekerja.create'))->assertForbidden();
    $this->actingAs($user)->post(route('pekerja.store'), pekerjaPayload())->assertForbidden();
    $this->actingAs($user)->get(route('pekerja.edit', $pekerja))->assertForbidden();
    $this->actingAs($user)->put(route('pekerja.update', $pekerja), pekerjaPayload())->assertForbidden();
    $this->actingAs($user)->delete(route('pekerja.destroy', $pekerja))->assertForbidden();
});

test('admin bisa melihat daftar pekerja dan mencari berdasarkan nama/pn', function () {
    $admin = User::factory()->admin()->create();
    Pekerja::factory()->create(['pn' => '90000010', 'nama' => 'Budi Santoso']);
    Pekerja::factory()->create(['pn' => '90000011', 'nama' => 'Siti Aminah']);

    $response = $this->actingAs($admin)->get(route('pekerja.index', ['q' => 'Budi']));

    $response->assertOk();
    expect($response->viewData('pekerjaList')->total())->toBe(1);
});

test('admin bisa menambah pekerja baru sehingga PN-nya bisa dipakai bikin user', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();

    $response = $this->actingAs($admin)->post(route('pekerja.store'), pekerjaPayload(['uker_kode' => $uker->kode]));

    $response->assertRedirect(route('pekerja.index'));
    $pekerja = Pekerja::where('pn', '90000001')->first();
    expect($pekerja)->not->toBeNull();
    expect($pekerja->nama)->toBe('Pekerja Percobaan');

    // PN yang baru dibuat ini sekarang harus lolos validasi exists:pekerja,pn
    // saat dipakai bikin akun User baru.
    $userResponse = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'User Baru',
        'pn' => '90000001',
        'password' => 'password123',
        'role' => 'user',
        'uker_kode' => $uker->kode,
    ]);
    $userResponse->assertRedirect(route('users.index'));
    expect(User::where('pn', '90000001')->exists())->toBeTrue();
});

test('pn pekerja harus unik saat ditambahkan', function () {
    $admin = User::factory()->admin()->create();
    $sudahAda = Pekerja::factory()->create(['pn' => '90000020']);

    $response = $this->actingAs($admin)->post(route('pekerja.store'), pekerjaPayload(['pn' => '90000020']));

    $response->assertSessionHasErrors('pn');
    expect(Pekerja::where('pn', '90000020')->count())->toBe(1);
});

test('admin bisa update data pekerja, PN tetap sama', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $pekerja = Pekerja::factory()->create(['pn' => '90000030', 'nama' => 'Nama Lama']);

    $response = $this->actingAs($admin)->put(
        route('pekerja.update', $pekerja),
        pekerjaPayload(['pn' => '90000030', 'nama' => 'Nama Baru', 'uker_kode' => $uker->kode])
    );

    $response->assertRedirect(route('pekerja.index'));
    expect($pekerja->fresh()->nama)->toBe('Nama Baru');
    expect($pekerja->fresh()->pn)->toBe('90000030');
});

test('pekerja tidak bisa dihapus kalau masih punya akun user terkait', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $pekerja = Pekerja::factory()->create(['pn' => '90000040']);
    User::factory()->forUker($uker->kode)->create(['pn' => '90000040']);

    $response = $this->actingAs($admin)->delete(route('pekerja.destroy', $pekerja));

    $response->assertRedirect();
    expect(Pekerja::where('pn', '90000040')->exists())->toBeTrue();
});

test('pekerja tidak bisa dihapus kalau masih jadi pic di form health check', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $pekerja = Pekerja::factory()->create(['pn' => '90000050']);
    HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'pic_pn' => '90000050']);

    $response = $this->actingAs($admin)->delete(route('pekerja.destroy', $pekerja));

    $response->assertRedirect();
    expect(Pekerja::where('pn', '90000050')->exists())->toBeTrue();
});

test('uker_kode wajib diisi saat menambah pekerja', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('pekerja.store'), pekerjaPayload());

    $response->assertSessionHasErrors('uker_kode');
    expect(Pekerja::where('pn', '90000001')->exists())->toBeFalse();
});

test('dropdown uker di form Tambah/Edit Pekerja cuma nampilin level KC ke atas, gak ada KCP/Unit', function () {
    $admin = User::factory()->admin()->create();
    $kanwil = Uker::factory()->create(['jenis' => 'KANWIL']);
    $kc = Uker::factory()->create(['jenis' => 'KC']);
    $kcp = Uker::factory()->create(['jenis' => 'KCP']);
    $unit = Uker::factory()->create(['jenis' => 'UNIT']);

    $create = $this->actingAs($admin)->get(route('pekerja.create'));
    $create->assertOk();
    $kodeDiCreate = $create->viewData('ukerList')->pluck('kode');
    expect($kodeDiCreate)->toContain($kanwil->kode, $kc->kode);
    expect($kodeDiCreate)->not->toContain($kcp->kode, $unit->kode);

    $pekerja = Pekerja::factory()->create(['uker_kode' => $kc->kode]);
    $edit = $this->actingAs($admin)->get(route('pekerja.edit', $pekerja));
    $edit->assertOk();
    $kodeDiEdit = $edit->viewData('ukerList')->pluck('kode');
    expect($kodeDiEdit)->toContain($kanwil->kode, $kc->kode);
    expect($kodeDiEdit)->not->toContain($kcp->kode, $unit->kode);
});

test('edit pekerja yang sudah ter-assign ke uker level KCP/Unit (data lama) tetap nampilin uker itu, gak ilang dari dropdown', function () {
    $admin = User::factory()->admin()->create();
    $kcp = Uker::factory()->create(['jenis' => 'KCP']);
    $pekerjaLama = Pekerja::factory()->create(['uker_kode' => $kcp->kode]);

    $edit = $this->actingAs($admin)->get(route('pekerja.edit', $pekerjaLama));

    $edit->assertOk();
    expect($edit->viewData('ukerList')->pluck('kode'))->toContain($kcp->kode);
});

test('pekerja tanpa data terkait bisa dihapus', function () {
    $admin = User::factory()->admin()->create();
    $pekerja = Pekerja::factory()->create(['pn' => '90000060']);

    $response = $this->actingAs($admin)->delete(route('pekerja.destroy', $pekerja));

    $response->assertRedirect(route('pekerja.index'));
    expect(Pekerja::where('pn', '90000060')->exists())->toBeFalse();
});

test('user biasa tidak bisa export kelola pekerja', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('pekerja.export.excel'))->assertForbidden();
    $this->actingAs($user)->get(route('pekerja.export.pdf'))->assertForbidden();
});

test('admin bisa export kelola pekerja Excel & PDF', function () {
    $admin = User::factory()->admin()->create();
    Pekerja::factory()->create();

    $this->actingAs($admin)->get(route('pekerja.export.excel'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($admin)->get(route('pekerja.export.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('PN pekerja baru harus 8 digit angka', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('pekerja.store'), pekerjaPayload(['pn' => '123']));

    $response->assertSessionHasErrors('pn');
    expect(Pekerja::where('pn', '123')->exists())->toBeFalse();
});

test('no_hp pekerja harus format nomor Indonesia yang valid', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();

    $response = $this->actingAs($admin)->post(route('pekerja.store'), pekerjaPayload(['uker_kode' => $uker->kode, 'no_hp' => 'bukan-nomor-hp']));

    $response->assertSessionHasErrors('no_hp');
});

test('no_hp pekerja dengan format strip maupun polos yang valid tetap diterima', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();

    $response = $this->actingAs($admin)->post(route('pekerja.store'), pekerjaPayload(['uker_kode' => $uker->kode, 'no_hp' => '0812-3456-7890']));

    $response->assertRedirect(route('pekerja.index'));
    expect(Pekerja::where('pn', '90000001')->first()?->no_hp)->toBe('0812-3456-7890');
});

test('edit pekerja lama yang PN-nya udah gak 8 digit (data legacy) tetap bisa disimpan selama PN gak diubah', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $pekerjaLegacy = Pekerja::factory()->create(['pn' => '00001', 'uker_kode' => $uker->kode]);

    $response = $this->actingAs($admin)->put(
        route('pekerja.update', $pekerjaLegacy),
        pekerjaPayload(['pn' => '00001', 'uker_kode' => $uker->kode, 'jabatan' => 'Jabatan Baru'])
    );

    $response->assertRedirect(route('pekerja.index'));
    expect($pekerjaLegacy->fresh()->jabatan)->toBe('Jabatan Baru');
});
