<?php

use App\Http\Controllers\AsetController;
use App\Http\Controllers\AsetEditRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LogHistoryController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PekerjaLookupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UkerController;
use App\Http\Controllers\UkerTreeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// App internal (selalu di balik login) -- gak perlu splash page publik,
// langsung arahkan ke dashboard (kalau udah login) atau halaman login.
Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Bulk upload/delete & export -- ditaruh SEBELUM Route::resource biar gak ketiban urutan
    Route::get('/aset/bulk-upload', [AsetController::class, 'bulkUploadForm'])->name('aset.bulkUploadForm');
    Route::get('/aset/template', [AsetController::class, 'downloadTemplate'])->name('aset.downloadTemplate');
    Route::post('/aset/bulk-upload', [AsetController::class, 'bulkUpload'])->name('aset.bulkUpload');
    Route::get('/aset/bulk-delete', [AsetController::class, 'bulkDeleteForm'])->name('aset.bulkDeleteForm');
    Route::post('/aset/bulk-delete', [AsetController::class, 'bulkDelete'])->name('aset.bulkDelete');
    Route::get('/aset/export/excel', [AsetController::class, 'exportExcel'])->name('aset.export.excel');
    Route::get('/aset/export/pdf', [AsetController::class, 'exportPdf'])->name('aset.export.pdf');
    Route::get('/aset/sampah', [AsetController::class, 'trash'])->name('aset.trash');
    Route::post('/aset/{id}/restore', [AsetController::class, 'restore'])->name('aset.restore');

    Route::get('/healthcheck/bulk-upload', [HealthCheckController::class, 'bulkUploadForm'])->name('healthcheck.bulkUploadForm');
    Route::get('/healthcheck/template', [HealthCheckController::class, 'downloadTemplate'])->name('healthcheck.downloadTemplate');
    Route::post('/healthcheck/bulk-upload', [HealthCheckController::class, 'bulkUpload'])->name('healthcheck.bulkUpload');
    Route::get('/healthcheck/bulk-delete', [HealthCheckController::class, 'bulkDeleteForm'])->name('healthcheck.bulkDeleteForm');
    Route::post('/healthcheck/bulk-delete', [HealthCheckController::class, 'bulkDelete'])->name('healthcheck.bulkDelete');
    Route::get('/healthcheck/export/excel', [HealthCheckController::class, 'exportExcel'])->name('healthcheck.export.excel');
    Route::get('/healthcheck/export/pdf', [HealthCheckController::class, 'exportPdf'])->name('healthcheck.export.pdf');
    Route::get('/healthcheck/sampah', [HealthCheckController::class, 'trash'])->name('healthcheck.trash');
    Route::post('/healthcheck/{id}/restore', [HealthCheckController::class, 'restore'])->name('healthcheck.restore');

    // Data aset (admin lihat semua, user/uker cuma lihat punya sendiri)
    Route::resource('aset', AsetController::class)->except(['show']);
    Route::post('/aset/{aset}/request-edit', [AsetController::class, 'requestEdit'])->name('aset.requestEdit');

    // Health check per uker
    Route::resource('healthcheck', HealthCheckController::class)->except(['show']);
    Route::post('/healthcheck/{healthcheck}/submit', [HealthCheckController::class, 'submitForApproval'])->name('healthcheck.submit');
    Route::post('/healthcheck/{healthcheck}/approve', [HealthCheckController::class, 'approve'])->name('healthcheck.approve');
    Route::post('/healthcheck/{healthcheck}/reject', [HealthCheckController::class, 'reject'])->name('healthcheck.reject');

    // Import data master (pekerja + uker + petugas IT) dari file Excel
    Route::get('/import', [ImportController::class, 'form']);
    Route::post('/import/pekerja', [ImportController::class, 'pekerja'])->name('import.pekerja');
    Route::post('/import/petugas-it', [ImportController::class, 'petugasIt'])->name('import.petugasIt');

    // API kecil buat auto-isi uker dari PN (dipanggil via fetch di form)
    Route::get('/api/pekerja-uker/{pn}', [PekerjaLookupController::class, 'lookupUker']);

    // Pencarian global topbar (dipanggil via fetch, live search dropdown)
    Route::get('/api/search', [SearchController::class, 'search'])->name('search.api');

    // Notifikasi in-app (lonceng di topbar) -- semua role, isinya di-scope ke
    // notifiable milik user yang login sendiri di controller-nya
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::get('/notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');

    // Kelola User, Rekap per Cabang, Log History, Permintaan Edit Aset, & Kelola Uker -- khusus admin
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggleActive');
        Route::get('/rekap-cabang', [RekapController::class, 'index'])->name('rekap.cabang');
        Route::get('/rekap-aset', [RekapController::class, 'aset'])->name('rekap.aset');
        Route::get('/monitoring-kendala', [MonitoringController::class, 'index'])->name('monitoring.index');
        Route::get('/monitoring-kendala/export/excel', [MonitoringController::class, 'exportExcel'])->name('monitoring.export.excel');
        Route::get('/monitoring-kendala/export/pdf', [MonitoringController::class, 'exportPdf'])->name('monitoring.export.pdf');
        Route::post('/monitoring-kendala/{item}/tindak-lanjut', [MonitoringController::class, 'updateTindakLanjut'])->name('monitoring.updateTindakLanjut');
        Route::get('/log-history', [LogHistoryController::class, 'index'])->name('log-history.index');
        Route::get('/log-history/export/excel', [LogHistoryController::class, 'exportExcel'])->name('log-history.export.excel');
        Route::get('/log-history/export/pdf', [LogHistoryController::class, 'exportPdf'])->name('log-history.export.pdf');
        Route::get('/aset-edit-requests', [AsetEditRequestController::class, 'index'])->name('aset.editRequests.index');
        Route::post('/aset-edit-requests/{editRequest}/approve', [AsetController::class, 'approveEdit'])->name('aset.editRequests.approve');
        Route::post('/aset-edit-requests/{editRequest}/reject', [AsetController::class, 'rejectEdit'])->name('aset.editRequests.reject');
        Route::resource('ukers', UkerController::class)->except(['show']);
        Route::get('/uker-tree', [UkerTreeController::class, 'index'])->name('uker-tree.index');
        Route::get('/uker-tree/{kode}/detail', [UkerTreeController::class, 'detail'])->name('uker-tree.detail');
        Route::get('/uker-tree/{kode}/compliance-detail', [UkerTreeController::class, 'complianceDetail'])->name('uker-tree.complianceDetail');
    });
});

require __DIR__.'/auth.php';
