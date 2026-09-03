<?php

use App\Models\Aset;
use App\Models\AsetKendala;
use App\Models\KodeAset;
use App\Models\Pekerja;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\AsetKendalaDilaporkan;
use App\Notifications\AsetKendalaStatusDiupdate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

test('user bisa lapor kerusakan aset yang bisa dia lihat, lengkap sama foto', function () {
    Storage::fake('public');

    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $foto = UploadedFile::fake()->image('rusak.jpg');

    $response = $this->actingAs($user)->post(route('monitoring.laporanAset.store', $aset), [
        'deskripsi' => 'Layar berkedip-kedip terus mati total',
        'foto' => $foto,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $kendala = AsetKendala::first();
    expect($kendala)->not->toBeNull();
    expect($kendala->aset_id)->toBe($aset->id);
    expect($kendala->deskripsi)->toBe('Layar berkedip-kedip terus mati total');
    expect($kendala->status)->toBe('Belum Ditindaklanjuti');
    expect($kendala->reported_by)->toBe($user->id);
    Storage::disk('public')->assertExists($kendala->foto_path);
});

test('lapor kerusakan tanpa foto tetap berhasil (foto opsional)', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $response = $this->actingAs($admin)->post(route('monitoring.laporanAset.store', $aset), [
        'deskripsi' => 'Keyboard beberapa tombol gak berfungsi',
    ]);

    $response->assertRedirect();
    $kendala = AsetKendala::first();
    expect($kendala->foto_path)->toBeNull();
});

test('lapor kerusakan ditolak kalau deskripsi kosong', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $response = $this->actingAs($admin)->post(route('monitoring.laporanAset.store', $aset), ['deskripsi' => '']);

    $response->assertSessionHasErrors('deskripsi');
});

test('lapor kerusakan ditolak kalau file bukan gambar', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $response = $this->actingAs($admin)->post(route('monitoring.laporanAset.store', $aset), [
        'deskripsi' => 'Rusak',
        'foto' => UploadedFile::fake()->create('dokumen.pdf', 100),
    ]);

    $response->assertSessionHasErrors('foto');
});

test('user gak bisa lapor kerusakan aset di luar subtree-nya', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $asetLain = Aset::factory()->create(['uker_kode' => $ukerLain->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $response = $this->actingAs($user)->post(route('monitoring.laporanAset.store', $asetLain), ['deskripsi' => 'Rusak']);

    $response->assertForbidden();
});

test('admin bisa lihat semua laporan kerusakan, user cuma subtree sendiri', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();
    $admin = User::factory()->admin()->create();
    $kodeAset = KodeAset::factory()->create();
    $asetSendiri = Aset::factory()->create(['uker_kode' => $ukerSendiri->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $asetLain = Aset::factory()->create(['uker_kode' => $ukerLain->kode, 'kode_aset_kode' => $kodeAset->kode]);

    AsetKendala::create(['aset_id' => $asetSendiri->id, 'deskripsi' => 'Punya sendiri', 'reported_by' => $user->id]);
    AsetKendala::create(['aset_id' => $asetLain->id, 'deskripsi' => 'Punya cabang lain', 'reported_by' => $admin->id]);

    $responseUser = $this->actingAs($user)->get(route('monitoring.laporanAset.index'));
    $responseUser->assertOk();
    $responseUser->assertSee('Punya sendiri');
    $responseUser->assertDontSee('Punya cabang lain');

    $responseAdmin = $this->actingAs($admin)->get(route('monitoring.laporanAset.index'));
    $responseAdmin->assertOk();
    $responseAdmin->assertSee('Punya sendiri');
    $responseAdmin->assertSee('Punya cabang lain');
});

test('halaman laporan kerusakan aset nampilin link WhatsApp petugas IT uker terkait', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    AsetKendala::create(['aset_id' => $aset->id, 'deskripsi' => 'Rusak parah', 'reported_by' => $admin->id]);
    Pekerja::factory()->create(['uker_kode' => $uker->kode, 'is_petugas_it' => true, 'no_hp' => '0812-3456-7890', 'nama' => 'Petugas Contoh']);

    $response = $this->actingAs($admin)->get(route('monitoring.laporanAset.index'));

    $response->assertOk();
    $response->assertSee('https://wa.me/6281234567890', false);
    $response->assertSee('Petugas Contoh');
});

test('admin bisa update status laporan kerusakan', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $kendala = AsetKendala::create(['aset_id' => $aset->id, 'deskripsi' => 'Rusak', 'reported_by' => $admin->id]);

    $response = $this->actingAs($admin)->post(route('monitoring.laporanAset.updateStatus', $kendala), [
        'status' => 'Sedang Diproses',
        'catatan_admin' => 'Udah diajukan ke vendor',
    ]);

    $response->assertRedirect();
    expect($kendala->fresh()->status)->toBe('Sedang Diproses');
    expect($kendala->fresh()->catatan_admin)->toBe('Udah diajukan ke vendor');
});

test('user gak bisa update status laporan kerusakan di luar subtree-nya', function () {
    $ukerSendiri = Uker::factory()->create();
    $ukerLain = Uker::factory()->create();
    $user = User::factory()->forUker($ukerSendiri->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $asetLain = Aset::factory()->create(['uker_kode' => $ukerLain->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $kendala = AsetKendala::create(['aset_id' => $asetLain->id, 'deskripsi' => 'Rusak', 'reported_by' => $user->id]);

    $response = $this->actingAs($user)->post(route('monitoring.laporanAset.updateStatus', $kendala), ['status' => 'Selesai Diperbaiki']);

    $response->assertForbidden();
});

test('semua admin dinotifikasi waktu ada laporan kerusakan baru', function () {
    Notification::fake();
    $admin1 = User::factory()->admin()->create();
    $admin2 = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $this->actingAs($user)->post(route('monitoring.laporanAset.store', $aset), ['deskripsi' => 'Rusak']);

    Notification::assertSentTo($admin1, AsetKendalaDilaporkan::class);
    Notification::assertSentTo($admin2, AsetKendalaDilaporkan::class);
});

test('pelapor dinotifikasi balik waktu admin update status laporannya', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $kendala = AsetKendala::create(['aset_id' => $aset->id, 'deskripsi' => 'Rusak', 'reported_by' => $user->id]);

    $this->actingAs($admin)->post(route('monitoring.laporanAset.updateStatus', $kendala), ['status' => 'Sedang Diproses']);

    Notification::assertSentTo($user, AsetKendalaStatusDiupdate::class);
});
