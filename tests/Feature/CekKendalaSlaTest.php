<?php

use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\Uker;
use App\Models\User;
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
