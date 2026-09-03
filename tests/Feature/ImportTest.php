<?php

use App\Models\ActivityLog;
use App\Models\Uker;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function buatUploadedXlsx(Spreadsheet $spreadsheet, string $namaFile): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return UploadedFile::fake()->createWithContent($namaFile, file_get_contents($path));
}

function buatFilePekerja(): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('DATABASE');

    $sheet->fromArray(['NO', 'PN', 'C', 'Nama', 'Jabatan', 'Status', 'G', 'KODE BRANCH', 'NAMA UKER', 'Kode Supervisi', 'Supervisi'], null, 'A1');
    // Baris 2: PN=00001, Nama=Budi, Jabatan=Staff, Status=Aktif, Kode Branch=101, Nama Uker=KC Test, Kode Spv=1, Supervisi=Kanwil Test
    $sheet->fromArray(['1', '00001', '', 'Budi', 'Staff', 'Aktif', '', '101', 'KC Test', '1', 'Kanwil Test'], null, 'A2');
    $sheet->fromArray(['2', '00002', '', 'Siti', 'Staff', 'Aktif', '', '101', 'KC Test', '1', 'Kanwil Test'], null, 'A3');

    return buatUploadedXlsx($spreadsheet, 'pekerja.xlsx');
}

function buatFilePetugasIt(): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $kanwil = $spreadsheet->getActiveSheet();
    $kanwil->setTitle('Kanwil');
    $kanwil->fromArray(['header'], null, 'A1');
    $kanwil->fromArray(['header'], null, 'A2');
    // Kolom B=PN, E=No HP, mulai baris 3
    $kanwil->setCellValue('B3', '00001');
    $kanwil->setCellValue('E3', '08123456789');

    $ro = $spreadsheet->createSheet();
    $ro->setTitle('Data Petugas IT RO Surabaya ');
    $ro->fromArray(['header'], null, 'A1');
    $ro->fromArray(['header'], null, 'A2');
    // Kolom D=PN, E=No HP, mulai baris 3
    $ro->setCellValue('D3', '00002');
    $ro->setCellValue('E3', '08199999999');

    return buatUploadedXlsx($spreadsheet, 'petugas-it.xlsx');
}

test('guest tidak bisa akses form import', function () {
    $this->get('/import')->assertRedirect(route('login'));
});

test('import pekerja mengisi tabel ukers dan pekerja', function () {
    $user = User::factory()->admin()->create();

    $response = $this->actingAs($user)->post(route('import.pekerja'), [
        'file' => buatFilePekerja(),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    expect(Uker::where('kode', 101)->first()?->nama)->toBe('KC Test');
    expect(DB::table('pekerja')->where('pn', '00000001')->first()?->nama)->toBe('Budi');
    expect(DB::table('pekerja')->where('pn', '00000002')->first()?->uker_kode)->toBe(101);

    $log = ActivityLog::where('modul', 'pekerja_uker')->first();
    expect($log)->not->toBeNull();
    expect($log->jumlah_baris)->toBe(2);
});

test('import pekerja pad PN kurang dari 8 digit dengan nol di depan, dan skip PN yang bukan angka murni', function () {
    $user = User::factory()->admin()->create();

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('DATABASE');
    $sheet->fromArray(['NO', 'PN', 'C', 'Nama', 'Jabatan', 'Status', 'G', 'KODE BRANCH', 'NAMA UKER', 'Kode Supervisi', 'Supervisi'], null, 'A1');
    // PN pendek (bukan teks 8 digit) -- harus dipad jadi 8 digit.
    $sheet->fromArray(['1', '13819', '', 'Pendek', 'Staff', 'Aktif', '', '101', 'KC Test', '1', 'Kanwil Test'], null, 'A2');
    // PN bukan angka murni -- harus di-skip, bukan ditulis apa adanya.
    $sheet->fromArray(['2', 'ABC123', '', 'Rusak', 'Staff', 'Aktif', '', '101', 'KC Test', '1', 'Kanwil Test'], null, 'A3');

    $response = $this->actingAs($user)->post(route('import.pekerja'), [
        'file' => buatUploadedXlsx($spreadsheet, 'pekerja-pn-aneh.xlsx'),
    ]);

    $response->assertRedirect();
    expect(DB::table('pekerja')->where('pn', '00013819')->first()?->nama)->toBe('Pendek');
    expect(DB::table('pekerja')->where('nama', 'Rusak')->exists())->toBeFalse();
    $response->assertSessionHas('status', fn ($status) => str_contains($status, '1 baris dilewati'));
});

test('import petugas IT menandai is_petugas_it berdasarkan PN', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user)->post(route('import.pekerja'), ['file' => buatFilePekerja()]);

    $response = $this->actingAs($user)->post(route('import.petugasIt'), [
        'file' => buatFilePetugasIt(),
    ]);

    $response->assertRedirect();

    $budi = DB::table('pekerja')->where('pn', '00000001')->first();
    $siti = DB::table('pekerja')->where('pn', '00000002')->first();
    expect((bool) $budi->is_petugas_it)->toBeTrue();
    expect($budi->no_hp)->toBe('08123456789');
    expect((bool) $siti->is_petugas_it)->toBeTrue();
    expect($siti->no_hp)->toBe('08199999999');
});

test('import petugas IT dengan PN yang gak ketemu di pekerja tetap lanjut jalan', function () {
    $user = User::factory()->admin()->create();
    // sengaja gak import pekerja dulu, jadi PN di file petugas IT gak akan ketemu.
    // Satu-satunya baris pekerja yang ada cuma pasangan dummy dari User factory
    // (dibutuhkan buat FK users.pn -> pekerja.pn sejak login pakai PN).
    $jumlahPekerjaSebelum = DB::table('pekerja')->count();

    $response = $this->actingAs($user)->post(route('import.petugasIt'), [
        'file' => buatFilePetugasIt(),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');
    expect(DB::table('pekerja')->count())->toBe($jumlahPekerjaSebelum);
});
