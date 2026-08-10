<?php

use App\Models\Aset;
use App\Models\AsetEditRequest;
use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\KodeAset;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\AsetEditRequestDecided;
use App\Notifications\AsetEditRequestSubmitted;
use App\Notifications\HealthCheckApprovalDecided;
use App\Notifications\HealthCheckItemFlaggedNotOk;
use App\Notifications\HealthCheckSubmittedForApproval;
use Illuminate\Support\Facades\Notification;

test('semua admin dinotifikasi waktu ada permintaan edit aset baru', function () {
    Notification::fake();

    $admin1 = User::factory()->admin()->create();
    $admin2 = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);

    $this->actingAs($user)->post(route('aset.requestEdit', $aset), ['alasan' => 'Uji notifikasi']);

    Notification::assertSentTo([$admin1, $admin2], AsetEditRequestSubmitted::class);
    Notification::assertNotSentTo($user, AsetEditRequestSubmitted::class); // requester sendiri gak perlu dinotif
});

test('requester dinotifikasi waktu permintaan edit aset disetujui atau ditolak', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $editRequest = AsetEditRequest::create(['aset_id' => $aset->id, 'requested_by' => $user->id, 'status' => 'Menunggu']);

    $this->actingAs($admin)->post(route('aset.editRequests.approve', $editRequest));

    Notification::assertSentTo($user, AsetEditRequestDecided::class);
});

test('semua admin dinotifikasi waktu form health check disubmit untuk approval', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->toDateString()]);

    $this->actingAs($user)->post(route('healthcheck.submit', $form));

    Notification::assertSentTo($admin, HealthCheckSubmittedForApproval::class);
});

test('user di uker terkait dinotifikasi waktu form health check disetujui/ditolak', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $userSatu = User::factory()->forUker($uker->kode)->create();
    $userDua = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'status_approval' => 'Menunggu Approval']);

    $this->actingAs($admin)->post(route('healthcheck.approve', $form));

    Notification::assertSentTo([$userSatu, $userDua], HealthCheckApprovalDecided::class);
});

test('admin dinotifikasi waktu ada item checklist yang baru jadi Not OK', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->toDateString()]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Belum Diperiksa']);

    $this->actingAs($user)->put(route('healthcheck.update', $form), [
        'items' => [
            ['id' => $item->id, 'status' => 'Not OK', 'catatan' => 'AC mati'],
        ],
        'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
    ]);

    Notification::assertSentTo($admin, HealthCheckItemFlaggedNotOk::class);
});

test('admin tidak dinotifikasi ulang kalau item yang sama disimpan lagi masih Not OK', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->toDateString()]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK']);

    $this->actingAs($user)->put(route('healthcheck.update', $form), [
        'items' => [
            ['id' => $item->id, 'status' => 'Not OK', 'catatan' => 'Masih AC mati, belum diperbaiki'],
        ],
        'status_tindak_lanjut' => 'Sedang Diproses',
    ]);

    Notification::assertNotSentTo($admin, HealthCheckItemFlaggedNotOk::class);
});

test('admin tidak dinotifikasi kalau tidak ada item yang jadi Not OK', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->toDateString()]);
    $item = HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Belum Diperiksa']);

    $this->actingAs($user)->put(route('healthcheck.update', $form), [
        'items' => [
            ['id' => $item->id, 'status' => 'OK', 'catatan' => null],
        ],
        'status_tindak_lanjut' => 'Belum Ditindaklanjuti',
    ]);

    Notification::assertNotSentTo($admin, HealthCheckItemFlaggedNotOk::class);
});

test('user bisa menandai satu notifikasi sudah dibaca dan diarahkan ke url terkait', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $editRequest = AsetEditRequest::create(['aset_id' => $aset->id, 'requested_by' => $user->id, 'status' => 'Disetujui']);

    $user->notify(new AsetEditRequestDecided($editRequest));
    $notif = $user->notifications()->first();
    expect($notif->read_at)->toBeNull();

    $response = $this->actingAs($user)->post(route('notifications.read', $notif->id));

    $response->assertRedirect(route('aset.edit', $aset));
    expect($notif->fresh()->read_at)->not->toBeNull();
});

test('user tidak bisa menandai notifikasi milik orang lain', function () {
    $uker = Uker::factory()->create();
    $userA = User::factory()->forUker($uker->kode)->create();
    $userB = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $editRequest = AsetEditRequest::create(['aset_id' => $aset->id, 'requested_by' => $userA->id, 'status' => 'Disetujui']);

    $userA->notify(new AsetEditRequestDecided($editRequest));
    $notif = $userA->notifications()->first();

    $this->actingAs($userB)->post(route('notifications.read', $notif->id))->assertNotFound();
});

test('poll notifikasi mengembalikan count unread dan daftar terbaru dalam json', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $editRequest = AsetEditRequest::create(['aset_id' => $aset->id, 'requested_by' => $user->id, 'status' => 'Disetujui']);

    $user->notify(new AsetEditRequestDecided($editRequest));
    $notifDibaca = $user->notifications()->first();
    $notifDibaca->markAsRead();
    $user->notify(new AsetEditRequestDecided($editRequest));

    $response = $this->actingAs($user)->getJson(route('notifications.poll'));

    $response->assertOk();
    $response->assertJson(['count' => 1]);
    expect($response->json('items'))->toHaveCount(2);
});

test('guest tidak bisa akses poll notifikasi', function () {
    $this->get(route('notifications.poll'))->assertRedirect(route('login'));
});

test('user bisa menandai semua notifikasi sudah dibaca sekaligus', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();
    $kodeAset = KodeAset::factory()->create();
    $aset = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $editRequest = AsetEditRequest::create(['aset_id' => $aset->id, 'requested_by' => $user->id, 'status' => 'Disetujui']);

    $user->notify(new AsetEditRequestDecided($editRequest));
    $user->notify(new AsetEditRequestDecided($editRequest));
    expect($user->unreadNotifications()->count())->toBe(2);

    $this->actingAs($user)->post(route('notifications.readAll'))->assertRedirect();

    expect($user->unreadNotifications()->count())->toBe(0);
});
