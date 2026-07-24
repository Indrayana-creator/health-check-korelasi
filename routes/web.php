<?php

use App\Http\Controllers\AsetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LogHistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Bulk upload/delete & export -- ditaruh SEBELUM Route::resource biar gak ketiban urutan
    Route::get('/aset/bulk-upload', [AsetController::class, 'bulkUploadForm'])->name('aset.bulkUploadForm');
    Route::post('/aset/bulk-upload', [AsetController::class, 'bulkUpload'])->name('aset.bulkUpload');
    Route::get('/aset/bulk-delete', [AsetController::class, 'bulkDeleteForm'])->name('aset.bulkDeleteForm');
    Route::post('/aset/bulk-delete', [AsetController::class, 'bulkDelete'])->name('aset.bulkDelete');
    Route::get('/aset/export/excel', [AsetController::class, 'exportExcel'])->name('aset.export.excel');
    Route::get('/aset/export/pdf', [AsetController::class, 'exportPdf'])->name('aset.export.pdf');

    Route::get('/healthcheck/bulk-upload', [HealthCheckController::class, 'bulkUploadForm'])->name('healthcheck.bulkUploadForm');
    Route::post('/healthcheck/bulk-upload', [HealthCheckController::class, 'bulkUpload'])->name('healthcheck.bulkUpload');
    Route::get('/healthcheck/bulk-delete', [HealthCheckController::class, 'bulkDeleteForm'])->name('healthcheck.bulkDeleteForm');
    Route::post('/healthcheck/bulk-delete', [HealthCheckController::class, 'bulkDelete'])->name('healthcheck.bulkDelete');
    Route::get('/healthcheck/export/excel', [HealthCheckController::class, 'exportExcel'])->name('healthcheck.export.excel');
    Route::get('/healthcheck/export/pdf', [HealthCheckController::class, 'exportPdf'])->name('healthcheck.export.pdf');

    // Data aset (admin lihat semua, user/uker cuma lihat punya sendiri)
    Route::resource('aset', AsetController::class)->except(['show']);

    // Health check per uker
    Route::resource('healthcheck', HealthCheckController::class)->except(['show']);

    // Import data master (pekerja + uker + petugas IT) dari file Excel
    Route::get('/import', [ImportController::class, 'form']);
    Route::post('/import/pekerja', [ImportController::class, 'pekerja'])->name('import.pekerja');
    Route::post('/import/petugas-it', [ImportController::class, 'petugasIt'])->name('import.petugasIt');
    
    // Kelola User, Rekap per Cabang, & Log History -- khusus admin
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('/rekap-cabang', [RekapController::class, 'index'])->name('rekap.cabang');
        Route::get('/log-history', [LogHistoryController::class, 'index'])->name('log-history.index');
    });
});

require __DIR__.'/auth.php';
