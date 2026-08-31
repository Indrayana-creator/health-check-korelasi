<?php

use App\Models\Aset;
use App\Models\AsetKondisiLog;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\ReminderAsetPerluPerhatian;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

test('user diingatkan kalau uker-nya punya aset perlu perhatian', function () {
    Notification::fake();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    // Belum ada riwayat kondisi sama sekali -- otomatis kehitung "belum pernah dicek".
    Aset::factory()->create(['uker_kode' => $uker->kode]);

    $this->artisan('aset:reminder-perlu-perhatian', ['--paksa' => true])->assertSuccessful();

    Notification::assertSentTo($user, ReminderAsetPerluPerhatian::class);
});

test('user tidak diingatkan kalau semua aset uker-nya aman', function () {
    Notification::fake();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kondisi' => 'NORMAL', 'tahun_perolehan' => now()->year]);
    AsetKondisiLog::create(['aset_id' => $aset->id, 'kondisi_lama' => 'NORMAL', 'kondisi_baru' => 'NORMAL', 'changed_by' => $user->id]);

    $this->artisan('aset:reminder-perlu-perhatian', ['--paksa' => true])->assertSuccessful();

    Notification::assertNothingSent();
});

test('tanpa --paksa, reminder cuma jalan hari Senin', function () {
    Notification::fake();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    Aset::factory()->create(['uker_kode' => $uker->kode]);
    $senin = now()->startOfWeek(Carbon::MONDAY);

    $this->travelTo($senin->copy()->addDay()); // Selasa -- bukan Senin
    $this->artisan('aset:reminder-perlu-perhatian')->assertSuccessful();
    Notification::assertNothingSent();

    $this->travelTo($senin); // Senin
    $this->artisan('aset:reminder-perlu-perhatian')->assertSuccessful();
    Notification::assertSentTo($user, ReminderAsetPerluPerhatian::class);

    $this->travelBack();
});

test('uker tanpa user aktif dilewati, gak bikin error', function () {
    Notification::fake();
    $uker = Uker::factory()->create();
    Aset::factory()->create(['uker_kode' => $uker->kode]); // gak ada user yang terikat ke uker ini

    $this->artisan('aset:reminder-perlu-perhatian', ['--paksa' => true])->assertSuccessful();

    Notification::assertNothingSent();
});

test('user cuma diingatkan sekali per minggu, gak spam tiap command dijalankan ulang', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    Aset::factory()->create(['uker_kode' => $uker->kode]);

    $this->artisan('aset:reminder-perlu-perhatian', ['--paksa' => true])->assertSuccessful();
    $this->artisan('aset:reminder-perlu-perhatian', ['--paksa' => true])->assertSuccessful();

    expect($user->notifications()->where('type', ReminderAsetPerluPerhatian::class)->count())->toBe(1);
});
