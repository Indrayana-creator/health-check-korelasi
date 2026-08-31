<?php

use App\Models\Aset;
use App\Models\KodeAset;
use App\Models\Pekerja;
use App\Models\Uker;
use App\Models\User;

function ukerPayload(Uker $induk, array $overrides = []): array
{
    return array_merge([
        'kode' => fake()->unique()->numberBetween(20000, 29999),
        'nama' => 'KC Percobaan',
        'alamat' => 'Jl. Percobaan No. 1',
        'jenis' => 'KC',
        'kode_spv' => $induk->kode,
    ], $overrides);
}

test('guest tidak bisa akses kelola uker', function () {
    $this->get(route('ukers.index'))->assertRedirect(route('login'));
});

test('user biasa tidak bisa akses kelola uker sama sekali', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('ukers.index'))->assertForbidden();
    $this->actingAs($user)->get(route('ukers.create'))->assertForbidden();
    $this->actingAs($user)->post(route('ukers.store'), ukerPayload($uker))->assertForbidden();
    $this->actingAs($user)->get(route('ukers.edit', $uker))->assertForbidden();
    $this->actingAs($user)->put(route('ukers.update', $uker), ukerPayload($uker))->assertForbidden();
    $this->actingAs($user)->delete(route('ukers.destroy', $uker))->assertForbidden();
});

test('admin bisa melihat daftar uker dan mencari berdasarkan nama/kode', function () {
    $admin = User::factory()->admin()->create();
    Uker::factory()->create(['nama' => 'KC Surabaya Darmo']);
    Uker::factory()->create(['nama' => 'KC Jakarta Sudirman']);

    $response = $this->actingAs($admin)->get(route('ukers.index', ['q' => 'Surabaya']));

    $response->assertOk();
    expect($response->viewData('ukers')->total())->toBe(1);
});

test('admin bisa menambah uker baru dan uker_spv otomatis ikut nama induk', function () {
    $admin = User::factory()->admin()->create();
    $induk = Uker::factory()->create(['nama' => 'Kanwil Jakarta']);

    $response = $this->actingAs($admin)->post(route('ukers.store'), ukerPayload($induk, ['kode' => 25001, 'nama' => 'KC Baru']));

    $response->assertRedirect(route('ukers.index'));
    $uker = Uker::where('kode', 25001)->first();
    expect($uker)->not->toBeNull();
    expect($uker->uker_spv)->toBe('Kanwil Jakarta');
});

test('kode uker harus unik saat ditambahkan', function () {
    $admin = User::factory()->admin()->create();
    $induk = Uker::factory()->create();
    $sudahAda = Uker::factory()->create();

    $response = $this->actingAs($admin)->post(route('ukers.store'), ukerPayload($induk, ['kode' => $sudahAda->kode]));

    $response->assertSessionHasErrors('kode');
    expect(Uker::where('kode', $sudahAda->kode)->count())->toBe(1);
});

test('admin bisa update uker dan uker_spv ikut berubah sesuai induk baru', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create(['kode' => 25002]);
    $indukBaru = Uker::factory()->create(['nama' => 'Kanwil Bandung']);

    $response = $this->actingAs($admin)->put(
        route('ukers.update', $uker),
        ukerPayload($indukBaru, ['kode' => $uker->kode, 'nama' => 'KC Sudah Diupdate'])
    );

    $response->assertRedirect(route('ukers.index'));
    expect($uker->fresh()->nama)->toBe('KC Sudah Diupdate');
    expect($uker->fresh()->uker_spv)->toBe('Kanwil Bandung');
});

test('uker dengan kode yang sama saat update tidak dianggap duplikat', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create(['kode' => 25003]);
    $induk = Uker::factory()->create();

    $response = $this->actingAs($admin)->put(
        route('ukers.update', $uker),
        ukerPayload($induk, ['kode' => $uker->kode])
    );

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('ukers.index'));
});

test('uker tidak bisa dihapus kalau masih punya data aset', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $response = $this->actingAs($admin)->delete(route('ukers.destroy', $uker));

    $response->assertRedirect();
    expect(Uker::where('kode', $uker->kode)->exists())->toBeTrue();
});

test('uker tidak bisa dihapus kalau masih punya data pekerja', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    Pekerja::create(['pn' => '00999888', 'nama' => 'Pekerja Uji', 'uker_kode' => $uker->kode]);

    $response = $this->actingAs($admin)->delete(route('ukers.destroy', $uker));

    $response->assertRedirect();
    expect(Uker::where('kode', $uker->kode)->exists())->toBeTrue();
});

test('uker tanpa data terkait bisa dihapus', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();

    $response = $this->actingAs($admin)->delete(route('ukers.destroy', $uker));

    $response->assertRedirect(route('ukers.index'));
    expect(Uker::where('kode', $uker->kode)->exists())->toBeFalse();
});

test('user biasa tidak bisa export kelola uker', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('ukers.export.excel'))->assertForbidden();
    $this->actingAs($user)->get(route('ukers.export.pdf'))->assertForbidden();
});

test('admin bisa export kelola uker Excel & PDF', function () {
    $admin = User::factory()->admin()->create();
    Uker::factory()->create();

    $this->actingAs($admin)->get(route('ukers.export.excel'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($admin)->get(route('ukers.export.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

// ===================== Riwayat Perubahan Uker =====================

test('update uker yang ganti nama & induk otomatis kecatat di riwayat perubahan', function () {
    $admin = User::factory()->admin()->create();
    $indukLama = Uker::factory()->create(['nama' => 'Kanwil Lama']);
    $indukBaru = Uker::factory()->create(['nama' => 'Kanwil Baru']);
    // jenis & alamat disamain persis sama default ukerPayload() di bawah,
    // biar cuma nama & kode_spv yang keitung berubah (bukan ikut kehitung
    // juga cuma karena beda dari nilai acak bawaan factory).
    $uker = Uker::factory()->create([
        'kode' => 26001, 'nama' => 'KC Nama Lama', 'jenis' => 'KC', 'alamat' => 'Jl. Percobaan No. 1',
        'kode_spv' => $indukLama->kode, 'uker_spv' => $indukLama->nama,
    ]);

    $this->actingAs($admin)->put(
        route('ukers.update', $uker),
        ukerPayload($indukBaru, ['kode' => $uker->kode, 'nama' => 'KC Nama Baru'])
    );

    $uker->refresh();
    $logs = $uker->perubahanLogs;
    expect($logs)->toHaveCount(2); // nama & kode_spv berubah

    $logNama = $logs->firstWhere('field', 'nama');
    expect($logNama->nilai_lama)->toBe('KC Nama Lama');
    expect($logNama->nilai_baru)->toBe('KC Nama Baru');
    expect($logNama->changed_by)->toBe($admin->id);

    $logInduk = $logs->firstWhere('field', 'kode_spv');
    expect((int) $logInduk->nilai_lama)->toBe($indukLama->kode);
    expect((int) $logInduk->nilai_baru)->toBe($indukBaru->kode);
});

test('update uker tanpa ganti apapun gak nambah riwayat perubahan', function () {
    $admin = User::factory()->admin()->create();
    $induk = Uker::factory()->create();
    $uker = Uker::factory()->create(['kode' => 26002, 'nama' => 'KC Tetap', 'jenis' => 'KC', 'alamat' => 'Jl. Tetap', 'kode_spv' => $induk->kode, 'uker_spv' => $induk->nama]);

    $this->actingAs($admin)->put(
        route('ukers.update', $uker),
        ukerPayload($induk, ['kode' => $uker->kode, 'nama' => 'KC Tetap', 'jenis' => 'KC', 'alamat' => 'Jl. Tetap'])
    );

    expect($uker->fresh()->perubahanLogs)->toHaveCount(0);
});

test('riwayat perubahan uker tampil di halaman edit', function () {
    $admin = User::factory()->admin()->create();
    $indukLama = Uker::factory()->create();
    $indukBaru = Uker::factory()->create();
    $uker = Uker::factory()->create(['kode' => 26003, 'nama' => 'KC Sebelum Ganti Nama', 'kode_spv' => $indukLama->kode]);

    $this->actingAs($admin)->put(
        route('ukers.update', $uker),
        ukerPayload($indukBaru, ['kode' => $uker->kode, 'nama' => 'KC Sesudah Ganti Nama'])
    );

    $response = $this->actingAs($admin)->get(route('ukers.edit', $uker));

    $response->assertOk();
    $response->assertSee('KC Sebelum Ganti Nama');
    $response->assertSee('KC Sesudah Ganti Nama');
});
