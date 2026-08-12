<?php

use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\KendalaMendesakCabang;
use App\Notifications\KendalaSlaTerlambat;
use Illuminate\Support\Facades\Notification;

test('admin dinotifikasi kalau ada item Not OK yang lewat ambang SLA', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->subDays(5)]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti']);

    $this->artisan('kendala:cek-sla')->assertSuccessful();

    Notification::assertSentTo($admin, KendalaSlaTerlambat::class);
});

test('admin tidak dinotifikasi kalau item Not OK belum lewat ambang SLA', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti']);

    $this->artisan('kendala:cek-sla')->assertSuccessful();

    Notification::assertNothingSent();
});

test('admin tidak dinotifikasi kalau item Not OK yang lewat ambang sudah Selesai Diperbaiki', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->subDays(5)]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Selesai Diperbaiki']);

    $this->artisan('kendala:cek-sla')->assertSuccessful();

    Notification::assertNothingSent();
});

test('admin cuma dinotifikasi sekali per hari, gak spam tiap command dijalankan ulang', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->subDays(5)]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti']);

    $this->artisan('kendala:cek-sla')->assertSuccessful();
    $this->artisan('kendala:cek-sla')->assertSuccessful();

    expect($admin->notifications()->where('type', KendalaSlaTerlambat::class)->count())->toBe(1);
});

// ===================== Notifikasi per-cabang (bukan admin) =====================

test('user cabang yang punya item mendesak sendiri ikut dinotifikasi (bukan cuma admin)', function () {
    Notification::fake();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->subDays(5)]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti']);

    $this->artisan('kendala:cek-sla')->assertSuccessful();

    Notification::assertSentTo($user, KendalaMendesakCabang::class);
});

test('user cabang lain yang gak punya item mendesak TIDAK dinotifikasi', function () {
    Notification::fake();
    $ukerBermasalah = Uker::factory()->create();
    $ukerAman = Uker::factory()->create();
    $userAman = User::factory()->forUker($ukerAman->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $ukerBermasalah->kode, 'tanggal_pemeriksaan' => now()->subDays(5)]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti']);

    $this->artisan('kendala:cek-sla')->assertSuccessful();

    Notification::assertNotSentTo($userAman, KendalaMendesakCabang::class);
});

test('user Cabang A ikut dinotifikasi kalau item mendesak ada di turunannya (KCP), bukan cuma di uker persis sama', function () {
    Notification::fake();
    $cabangA = Uker::factory()->create();
    $kcpA1 = Uker::factory()->create(['kode_spv' => $cabangA->kode]);
    $user = User::factory()->forUker($cabangA->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $kcpA1->kode, 'tanggal_pemeriksaan' => now()->subDays(5)]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti']);

    $this->artisan('kendala:cek-sla')->assertSuccessful();

    Notification::assertSentTo($user, KendalaMendesakCabang::class);
});

test('user Cabang A TIDAK dinotifikasi buat item mendesak milik Cabang B (bukan bagian subtree-nya)', function () {
    Notification::fake();
    $cabangA = Uker::factory()->create();
    $cabangB = Uker::factory()->create();
    $userA = User::factory()->forUker($cabangA->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $cabangB->kode, 'tanggal_pemeriksaan' => now()->subDays(5)]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti']);

    $this->artisan('kendala:cek-sla')->assertSuccessful();

    Notification::assertNotSentTo($userA, KendalaMendesakCabang::class);
});

test('user cabang cuma dinotifikasi sekali per hari, gak spam tiap command dijalankan ulang', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->subDays(5)]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti']);

    $this->artisan('kendala:cek-sla')->assertSuccessful();
    $this->artisan('kendala:cek-sla')->assertSuccessful();

    expect($user->notifications()->where('type', KendalaMendesakCabang::class)->count())->toBe(1);
});

test('admin dan user cabang SAMA-SAMA dinotifikasi dalam satu kali jalan command (bukan salah satu doang)', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->subDays(5)]);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK', 'status_tindak_lanjut' => 'Belum Ditindaklanjuti']);

    $this->artisan('kendala:cek-sla')->assertSuccessful();

    Notification::assertSentTo($admin, KendalaSlaTerlambat::class);
    Notification::assertSentTo($user, KendalaMendesakCabang::class);
});
