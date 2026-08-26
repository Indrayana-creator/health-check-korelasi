<?php

use App\Models\Aset;
use App\Models\AsetKondisiLog;
use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\KodeAset;
use App\Models\PermintaanPerangkat;
use App\Models\Uker;
use App\Models\User;
use Illuminate\Support\Carbon;

test('user biasa tidak bisa akses rekap cabang', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('rekap.cabang'))->assertForbidden();
});

test('rekap minggu ini menjumlahkan compliance semua uker dalam satu cabang (uker_spv)', function () {
    $admin = User::factory()->admin()->create();

    $ukerA = Uker::factory()->create(['uker_spv' => 'Cabang Surabaya']);
    $ukerB = Uker::factory()->create(['uker_spv' => 'Cabang Surabaya']);

    $formA = HealthCheckForm::factory()->create(['uker_kode' => $ukerA->kode, 'tanggal_pemeriksaan' => now()->startOfWeek(Carbon::MONDAY)]);
    HealthCheckItem::factory()->count(2)->create(['health_check_form_id' => $formA->id, 'status' => 'OK']);
    HealthCheckItem::factory()->count(2)->create(['health_check_form_id' => $formA->id, 'status' => 'Not OK']);

    $formB = HealthCheckForm::factory()->create(['uker_kode' => $ukerB->kode, 'tanggal_pemeriksaan' => now()->startOfWeek(Carbon::MONDAY)]);
    HealthCheckItem::factory()->count(4)->create(['health_check_form_id' => $formB->id, 'status' => 'OK']);

    $response = $this->actingAs($admin)->get(route('rekap.cabang'));

    $response->assertOk();
    $rekap = $response->viewData('rekapMingguan')->firstWhere('cabang', 'Cabang Surabaya');

    expect($rekap)->not->toBeNull();
    expect($rekap['jumlah_uker_lapor'])->toBe(2);
    expect($rekap['total_item'])->toBe(8);
    expect($rekap['ok'])->toBe(6);
    // 6 OK dari 8 total = 75%, di bawah ambang 80% jadi "PERLU PERHATIAN"
    expect($rekap['persen'])->toBe(75.0);
    expect($rekap['status'])->toBe('PERLU PERHATIAN');
});

test('rekap minggu ini TIDAK ikut menghitung form dari minggu lalu, otomatis refresh begitu ganti minggu', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create(['uker_spv' => 'Cabang Surabaya']);

    $formMingguLalu = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->subWeeks(1)]);
    HealthCheckItem::factory()->count(4)->create(['health_check_form_id' => $formMingguLalu->id, 'status' => 'Not OK']);

    $response = $this->actingAs($admin)->get(route('rekap.cabang'));

    $response->assertOk();
    expect($response->viewData('rekapMingguan')->firstWhere('cabang', 'Cabang Surabaya'))->toBeNull();
});

test('rekap bulan ini menggabungkan form dari minggu lalu SELAMA masih di bulan yang sama', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create(['uker_spv' => 'Cabang Surabaya']);

    // Minggu ini
    $formMingguIni = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()]);
    HealthCheckItem::factory()->count(2)->create(['health_check_form_id' => $formMingguIni->id, 'status' => 'OK']);

    // Awal bulan yang sama, tapi minggu berbeda -- tetap ikut rekap bulanan
    $awalBulan = now()->copy()->startOfMonth();
    $formAwalBulan = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => $awalBulan]);
    HealthCheckItem::factory()->count(2)->create(['health_check_form_id' => $formAwalBulan->id, 'status' => 'OK']);

    // Bulan lalu -- TIDAK boleh ikut kehitung
    $formBulanLalu = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->copy()->subMonthNoOverflow()]);
    HealthCheckItem::factory()->count(4)->create(['health_check_form_id' => $formBulanLalu->id, 'status' => 'Not OK']);

    $response = $this->actingAs($admin)->get(route('rekap.cabang'));

    $response->assertOk();
    $rekapBulanan = $response->viewData('rekapBulanan')->firstWhere('cabang', 'Cabang Surabaya');

    expect($rekapBulanan)->not->toBeNull();
    expect($rekapBulanan['total_item'])->toBe(4);
    expect($rekapBulanan['ok'])->toBe(4);
    expect($rekapBulanan['persen'])->toBe(100.0);
});

test('tren compliance diurutkan kronologis berdasarkan tanggal pemeriksaan paling awal, bukan alfabetis periode', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();

    // "Agustus" duluan dibuat tapi tanggalnya belakangan -- alfabetis bakal
    // salah urutan (A < J), jadi harus ngikutin tanggal_pemeriksaan
    $formAgustus = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'periode' => 'Agustus 2026', 'tanggal_pemeriksaan' => '2026-08-05']);
    HealthCheckItem::factory()->count(4)->create(['health_check_form_id' => $formAgustus->id, 'status' => 'OK']);

    $formJuli = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'periode' => 'Juli 2026', 'tanggal_pemeriksaan' => '2026-07-10']);
    HealthCheckItem::factory()->count(2)->create(['health_check_form_id' => $formJuli->id, 'status' => 'OK']);
    HealthCheckItem::factory()->count(2)->create(['health_check_form_id' => $formJuli->id, 'status' => 'Not OK']);

    $response = $this->actingAs($admin)->get(route('rekap.cabang'));

    $response->assertOk();
    $tren = $response->viewData('trenCompliance');

    expect($tren)->toHaveCount(2);
    expect($tren[0]['periode'])->toBe('Juli 2026');
    expect($tren[0]['persen'])->toBe(50.0);
    expect($tren[1]['periode'])->toBe('Agustus 2026');
    expect($tren[1]['persen'])->toBe(100.0);
});

test('user biasa tidak bisa akses rekap aset per cabang', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('rekap.aset'))->assertForbidden();
});

test('rekap aset menjumlahkan kondisi aset semua uker dalam satu cabang (uker_spv)', function () {
    $admin = User::factory()->admin()->create();
    $kodeAset = KodeAset::factory()->create();

    $ukerA = Uker::factory()->create(['uker_spv' => 'Cabang Surabaya']);
    $ukerB = Uker::factory()->create(['uker_spv' => 'Cabang Surabaya']);

    Aset::factory()->count(6)->create(['uker_kode' => $ukerA->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'NORMAL']);
    Aset::factory()->count(2)->create(['uker_kode' => $ukerA->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'RUSAK']);
    Aset::factory()->count(1)->create(['uker_kode' => $ukerB->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'TIDAK LAYAK']);
    Aset::factory()->count(1)->create(['uker_kode' => $ukerB->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'BACKUP']);

    $response = $this->actingAs($admin)->get(route('rekap.aset'));

    $response->assertOk();
    $rekap = $response->viewData('rekap')->firstWhere('cabang', 'Cabang Surabaya');

    expect($rekap)->not->toBeNull();
    expect($rekap['jumlah_uker_lapor'])->toBe(2);
    expect($rekap['total'])->toBe(10);
    expect($rekap['normal'])->toBe(6);
    expect($rekap['rusak'])->toBe(2);
    expect($rekap['tidak_layak'])->toBe(1);
    expect($rekap['lainnya'])->toBe(1); // BACKUP, gak masuk 3 kategori spesifik di atas
    // 6 normal dari 10 total = 60%, di bawah ambang 80% jadi "PERLU PERHATIAN"
    expect($rekap['persen_sehat'])->toBe(60.0);
    expect($rekap['status'])->toBe('PERLU PERHATIAN');

    $distribusiKondisi = $response->viewData('distribusiKondisi');
    expect($distribusiKondisi)->toBe([
        'Normal' => 6,
        'Rusak' => 2,
        'Tidak Layak' => 1,
        'Lainnya' => 1,
    ]);
});

test('rekap aset ngitung persentase kelengkapan data per cabang', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create(['uker_spv' => 'Cabang Malang']);
    $kodeAsetIndividu = KodeAset::factory()->create(['kategori' => 'PERSONAL COMPUTER']);
    $kodeAsetLain = KodeAset::factory()->create(['kategori' => 'HARDISK']);

    // Kategori individu, semua field pemegang & keamanan lengkap -- HARUS lengkap
    Aset::factory()->create([
        'uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAsetIndividu->kode,
        'pemegang_nama' => 'Budi', 'jabatan' => 'Staff', 'pemegang_pn' => '90000001',
        'ip_address' => '10.0.0.1', 'status_hardening' => 'Sudah', 'status_bitlocker' => 'Aktif',
        'status_dlp' => 'Aktif', 'status_antivirus' => 'Aktif',
    ]);
    // Kategori individu, tapi status_hardening kosong -- HARUS gak lengkap
    Aset::factory()->create([
        'uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAsetIndividu->kode,
        'pemegang_nama' => 'Ani', 'jabatan' => 'Staff', 'pemegang_pn' => '90000002',
        'ip_address' => '10.0.0.2', 'status_hardening' => null, 'status_bitlocker' => 'Aktif',
        'status_dlp' => 'Aktif', 'status_antivirus' => 'Aktif',
    ]);
    // Kategori BUKAN individu -- lengkap asal merek & SN keisi, gak butuh field pemegang
    Aset::factory()->create([
        'uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAsetLain->kode,
        'pemegang_nama' => null, 'jabatan' => null, 'pemegang_pn' => null,
        'ip_address' => null, 'status_hardening' => null, 'status_bitlocker' => null,
        'status_dlp' => null, 'status_antivirus' => null,
    ]);

    $response = $this->actingAs($admin)->get(route('rekap.aset'));

    $rekap = $response->viewData('rekap')->firstWhere('cabang', 'Cabang Malang');

    // 2 dari 3 aset lengkap = 66.7%
    expect($rekap['persen_lengkap'])->toBe(66.7);
});

test('rekap aset ngitung tren perubahan kondisi per bulan', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $kodeAset = KodeAset::factory()->create();
    $aset1 = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);
    $aset2 = Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode]);

    // created_at bukan mass-assignable (log ini insert-only, timestamp-nya
    // seharusnya selalu "now" di kondisi normal) -- forceFill dipakai di sini
    // biar bisa nyimulasiin data BULAN LALU buat tes tren lintas periode.
    AsetKondisiLog::create(['aset_id' => $aset1->id, 'kondisi_lama' => 'NORMAL', 'kondisi_baru' => 'RUSAK', 'changed_by' => $admin->id])
        ->forceFill(['created_at' => now()->startOfMonth()])->save();
    AsetKondisiLog::create(['aset_id' => $aset2->id, 'kondisi_lama' => 'RUSAK', 'kondisi_baru' => 'NORMAL', 'changed_by' => $admin->id])
        ->forceFill(['created_at' => now()->subMonth()->startOfMonth()])->save();

    $response = $this->actingAs($admin)->get(route('rekap.aset'));

    $response->assertOk();
    $tren = $response->viewData('trenKondisi');
    expect($tren)->toHaveCount(2);
    expect(collect($tren)->firstWhere('bulan', now()->format('Y-m'))['baru_rusak'])->toBe(1);
    expect(collect($tren)->firstWhere('bulan', now()->subMonth()->format('Y-m'))['diperbaiki'])->toBe(1);
});

// ===================== Export =====================

test('user biasa tidak bisa export rekap cabang maupun rekap aset', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('rekap.cabang.export.excel'))->assertForbidden();
    $this->actingAs($user)->get(route('rekap.cabang.export.pdf'))->assertForbidden();
    $this->actingAs($user)->get(route('rekap.aset.export.excel'))->assertForbidden();
    $this->actingAs($user)->get(route('rekap.aset.export.pdf'))->assertForbidden();
});

test('export rekap cabang Excel & PDF berhasil, ngikutin ?periode= yang lagi aktif', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create();
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()]);
    HealthCheckItem::factory()->count(2)->create(['health_check_form_id' => $form->id, 'status' => 'OK']);

    $responseExcelMinggu = $this->actingAs($admin)->get(route('rekap.cabang.export.excel', ['periode' => 'minggu']));
    $responseExcelMinggu->assertOk();
    $responseExcelMinggu->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $responsePdfBulan = $this->actingAs($admin)->get(route('rekap.cabang.export.pdf', ['periode' => 'bulan']));
    $responsePdfBulan->assertOk();
    $responsePdfBulan->assertHeader('content-type', 'application/pdf');
});

test('export rekap aset Excel & PDF berhasil', function () {
    $admin = User::factory()->admin()->create();
    $kodeAset = KodeAset::factory()->create();
    $uker = Uker::factory()->create();
    Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'NORMAL']);

    $this->actingAs($admin)->get(route('rekap.aset.export.excel'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($admin)->get(route('rekap.aset.export.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

// ===================== Kartu Skor Cabang =====================

test('user biasa tidak bisa akses kartu skor cabang', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('rekap.skorCabang'))->assertForbidden();
});

test('kartu skor cabang menggabungkan compliance HC, persen sehat, persen lengkap, & SLA permintaan jadi satu skor', function () {
    $admin = User::factory()->admin()->create();
    $uker = Uker::factory()->create(['uker_spv' => 'Cabang Skor']);
    $kodeAset = KodeAset::factory()->create(['kategori' => 'HARDISK']);

    // HC: 4 OK dari 5 item, form bulan ini -- compliance 80%
    $form = HealthCheckForm::factory()->create(['uker_kode' => $uker->kode, 'tanggal_pemeriksaan' => now()->toDateString()]);
    HealthCheckItem::factory()->count(4)->create(['health_check_form_id' => $form->id, 'status' => 'OK']);
    HealthCheckItem::factory()->create(['health_check_form_id' => $form->id, 'status' => 'Not OK']);

    // Aset: 4 NORMAL dari 5 aset -- sehat 80%, semua kategori non-individu jadi otomatis lengkap 100%
    Aset::factory()->count(4)->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'NORMAL']);
    Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'RUSAK']);

    // Permintaan Perangkat: 4 tepat waktu dari 5 yang masih terbuka -- SLA 80%
    PermintaanPerangkat::factory()->count(4)->create(['uker_kode' => $uker->kode, 'status' => 'Pending IT', 'created_at' => now()->subDays(2)]);
    PermintaanPerangkat::factory()->create(['uker_kode' => $uker->kode, 'status' => 'Pending IT', 'created_at' => now()->subDays(10)]);

    $response = $this->actingAs($admin)->get(route('rekap.skorCabang'));

    $response->assertOk();
    $entry = $response->viewData('skor')->firstWhere('cabang', 'Cabang Skor');

    expect($entry)->not->toBeNull();
    expect($entry['compliance_hc'])->toBe(80.0);
    expect($entry['persen_sehat'])->toBe(80.0);
    expect($entry['persen_lengkap'])->toBe(100.0);
    expect($entry['sla_permintaan'])->toBe(80.0);
    expect($entry['skor_gabungan'])->toBe(85.0);
});

test('kartu skor cabang diurutkan dari skor tertinggi', function () {
    $admin = User::factory()->admin()->create();
    $kodeAset = KodeAset::factory()->create(['kategori' => 'HARDISK']);

    $ukerBagus = Uker::factory()->create(['uker_spv' => 'Cabang Bagus']);
    Aset::factory()->count(5)->create(['uker_kode' => $ukerBagus->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'NORMAL']);

    $ukerJelek = Uker::factory()->create(['uker_spv' => 'Cabang Jelek']);
    Aset::factory()->count(5)->create(['uker_kode' => $ukerJelek->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'RUSAK']);

    $response = $this->actingAs($admin)->get(route('rekap.skorCabang'));

    $skor = $response->viewData('skor');
    expect($skor->first()['cabang'])->toBe('Cabang Bagus');
    expect($skor->last()['cabang'])->toBe('Cabang Jelek');
});

test('export kartu skor cabang Excel & PDF berhasil', function () {
    $admin = User::factory()->admin()->create();
    $kodeAset = KodeAset::factory()->create();
    $uker = Uker::factory()->create();
    Aset::factory()->create(['uker_kode' => $uker->kode, 'kode_aset_kode' => $kodeAset->kode, 'kondisi' => 'NORMAL']);

    $this->actingAs($admin)->get(route('rekap.skorCabang.export.excel'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($admin)->get(route('rekap.skorCabang.export.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
