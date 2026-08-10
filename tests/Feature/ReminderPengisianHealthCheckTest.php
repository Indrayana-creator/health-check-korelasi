<?php

use App\Models\HealthCheckForm;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\ReminderPengisianHealthCheck;
use Illuminate\Support\Facades\Notification;

test('user diingatkan kalau uker-nya belum bikin form health check bulan ini', function () {
    Notification::fake();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->artisan('healthcheck:reminder-pengisian', ['--paksa' => true])->assertSuccessful();

    Notification::assertSentTo($user, ReminderPengisianHealthCheck::class);
});

test('user tidak diingatkan kalau uker-nya sudah bikin form health check bulan ini', function () {
    Notification::fake();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()]);

    $this->artisan('healthcheck:reminder-pengisian', ['--paksa' => true])->assertSuccessful();

    Notification::assertNothingSent();
});

test('tanpa --paksa, reminder cuma jalan pas masuk window H-3 sebelum akhir bulan', function () {
    Notification::fake();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $sisaHari = now()->daysInMonth - now()->day;

    $this->artisan('healthcheck:reminder-pengisian')->assertSuccessful();

    if ($sisaHari > 3) {
        Notification::assertNothingSent();
    } else {
        Notification::assertSentTo($user, ReminderPengisianHealthCheck::class);
    }
});

test('uker tanpa user aktif dilewati, gak bikin error', function () {
    Notification::fake();
    Uker::factory()->create(); // gak ada user yang terikat ke uker ini

    $this->artisan('healthcheck:reminder-pengisian', ['--paksa' => true])->assertSuccessful();

    Notification::assertNothingSent();
});

test('user cuma diingatkan sekali per bulan, gak spam tiap command dijalankan ulang', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->artisan('healthcheck:reminder-pengisian', ['--paksa' => true])->assertSuccessful();
    $this->artisan('healthcheck:reminder-pengisian', ['--paksa' => true])->assertSuccessful();

    expect($user->notifications()->where('type', ReminderPengisianHealthCheck::class)->count())->toBe(1);
});
