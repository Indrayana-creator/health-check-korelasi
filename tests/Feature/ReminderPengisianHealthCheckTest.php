<?php

use App\Models\HealthCheckForm;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\ReminderPengisianHealthCheck;
use App\Support\PeriodeMingguan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

test('user diingatkan kalau uker-nya belum bikin form health check minggu ini', function () {
    Notification::fake();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->artisan('healthcheck:reminder-pengisian', ['--paksa' => true])->assertSuccessful();

    Notification::assertSentTo($user, ReminderPengisianHealthCheck::class);
});

test('user tidak diingatkan kalau uker-nya sudah bikin form health check minggu ini', function () {
    Notification::fake();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    [$senin] = PeriodeMingguan::rentang(now());
    HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => $senin]);

    $this->artisan('healthcheck:reminder-pengisian', ['--paksa' => true])->assertSuccessful();

    Notification::assertNothingSent();
});

test('tanpa --paksa, reminder cuma jalan hari Kamis (H-1 sebelum Jumat)', function () {
    Notification::fake();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $senin = now()->startOfWeek(Carbon::MONDAY);

    $this->travelTo($senin); // Senin -- belum hari Kamis
    $this->artisan('healthcheck:reminder-pengisian')->assertSuccessful();
    Notification::assertNothingSent();

    $this->travelTo($senin->copy()->addDays(3)); // Kamis
    $this->artisan('healthcheck:reminder-pengisian')->assertSuccessful();
    Notification::assertSentTo($user, ReminderPengisianHealthCheck::class);

    $this->travelBack();
});

test('tanpa --paksa, reminder gak jalan lagi di hari Jumat (sudah lewat H-1)', function () {
    Notification::fake();
    $uker = Uker::factory()->create();
    User::factory()->forUker($uker->kode)->create();
    $jumat = now()->startOfWeek(Carbon::MONDAY)->addDays(4);

    $this->travelTo($jumat);
    $this->artisan('healthcheck:reminder-pengisian')->assertSuccessful();

    Notification::assertNothingSent();

    $this->travelBack();
});

test('uker tanpa user aktif dilewati, gak bikin error', function () {
    Notification::fake();
    Uker::factory()->create(); // gak ada user yang terikat ke uker ini

    $this->artisan('healthcheck:reminder-pengisian', ['--paksa' => true])->assertSuccessful();

    Notification::assertNothingSent();
});

test('user cuma diingatkan sekali per minggu, gak spam tiap command dijalankan ulang', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->artisan('healthcheck:reminder-pengisian', ['--paksa' => true])->assertSuccessful();
    $this->artisan('healthcheck:reminder-pengisian', ['--paksa' => true])->assertSuccessful();

    expect($user->notifications()->where('type', ReminderPengisianHealthCheck::class)->count())->toBe(1);
});

test('form yang diperiksa minggu lalu gak dianggap sudah isi minggu ini', function () {
    Notification::fake();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    [$seninMingguIni] = PeriodeMingguan::rentang(now());
    $mingguLalu = $seninMingguIni->copy()->subWeek();
    HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => $mingguLalu]);

    $this->artisan('healthcheck:reminder-pengisian', ['--paksa' => true])->assertSuccessful();

    Notification::assertSentTo($user, ReminderPengisianHealthCheck::class);
});
