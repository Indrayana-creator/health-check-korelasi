<?php

use App\Http\Controllers\AsetController;
use App\Http\Controllers\AsetEditRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\KodeAsetController;
use App\Http\Controllers\LogHistoryController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PekerjaController;
use App\Http\Controllers\PekerjaLookupController;
use App\Http\Controllers\PermintaanPerangkatController;
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
// /api/* -- satu-satunya prefix yang bikin exception (termasuk gagal
// validasi) di-render sebagai JSON (lihat bootstrap/app.php shouldRenderJsonWhen),
// wajib dipakai buat endpoint yang dipanggil via fetch() kayak gini.
Route::get('/api/dashboard/item-detail', [DashboardController::class, 'itemDetail'])->middleware(['auth', 'verified'])->name('dashboard.itemDetail');
Route::post('/dashboard/kirim-pengingat-hc/{uker}', [DashboardController::class, 'kirimPengingatHc'])->middleware(['auth', 'verified'])->name('dashboard.kirimPengingatHc');
Route::post('/dashboard/kirim-pengingat-aset/{uker}', [DashboardController::class, 'kirimPengingatAset'])->middleware(['auth', 'verified'])->name('dashboard.kirimPengingatAset');

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

    // Struktur Organisasi -- semua role, tapi root tree-nya beda: admin dari
    // Kanwil (lihat semua), user dari uker_kode sendiri (cuma subtree-nya).
    // Scoping detailnya di-cek di dalam controller, bukan lewat middleware
    // role, karena admin & user SAMA-SAMA boleh akses route ini.
    Route::get('/uker-tree', [UkerTreeController::class, 'index'])->name('uker-tree.index');
    Route::get('/uker-tree/export/excel', [UkerTreeController::class, 'exportExcel'])->name('uker-tree.export.excel');
    Route::get('/uker-tree/export/pdf', [UkerTreeController::class, 'exportPdf'])->name('uker-tree.export.pdf');
    Route::get('/uker-tree/{kode}/detail', [UkerTreeController::class, 'detail'])->name('uker-tree.detail');
    Route::get('/uker-tree/{kode}/compliance-detail', [UkerTreeController::class, 'complianceDetail'])->name('uker-tree.complianceDetail');

    // Monitoring Kendala -- semua role, di-scope subtree (admin: semua,
    // user: uker sendiri + turunan) sama kayak Struktur Organisasi di atas.
    Route::get('/monitoring-kendala', [MonitoringController::class, 'index'])->name('monitoring.index');
    Route::get('/monitoring-kendala/export/excel', [MonitoringController::class, 'exportExcel'])->name('monitoring.export.excel');
    Route::get('/monitoring-kendala/export/pdf', [MonitoringController::class, 'exportPdf'])->name('monitoring.export.pdf');
    Route::post('/monitoring-kendala/{item}/tindak-lanjut', [MonitoringController::class, 'updateTindakLanjut'])->name('monitoring.updateTindakLanjut');

    // Permintaan Perangkat -- semua role, tapi di-scope EXACT MATCH uker_kode
    // sendiri buat non-admin (BUKAN subtree/turunan kayak Aset/HealthCheck,
    // karena yang ngajuin cuma level KC/Cabang). Cuma role user yang bisa
    // ajukan baru (store), update status khusus admin (di grup role:admin
    // di bawah).
    Route::get('/permintaan-perangkat', [PermintaanPerangkatController::class, 'index'])->name('permintaan-perangkat.index');
    Route::post('/permintaan-perangkat', [PermintaanPerangkatController::class, 'store'])->name('permintaan-perangkat.store');
    Route::get('/permintaan-perangkat/export/excel', [PermintaanPerangkatController::class, 'exportExcel'])->name('permintaan-perangkat.export.excel');
    Route::get('/permintaan-perangkat/export/pdf', [PermintaanPerangkatController::class, 'exportPdf'])->name('permintaan-perangkat.export.pdf');

    // Kelola User, Rekap per Cabang, Log History, Permintaan Edit Aset, & Kelola Uker -- khusus admin
    Route::middleware('role:admin')->group(function () {
        Route::get('/users/export/excel', [UserController::class, 'exportExcel'])->name('users.export.excel');
        Route::get('/users/export/pdf', [UserController::class, 'exportPdf'])->name('users.export.pdf');
        Route::resource('users', UserController::class)->except(['show']);
        Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggleActive');
        Route::get('/rekap-cabang', [RekapController::class, 'index'])->name('rekap.cabang');
        Route::get('/rekap-cabang/export/excel', [RekapController::class, 'exportCabangExcel'])->name('rekap.cabang.export.excel');
        Route::get('/rekap-cabang/export/pdf', [RekapController::class, 'exportCabangPdf'])->name('rekap.cabang.export.pdf');
        Route::get('/rekap-aset', [RekapController::class, 'aset'])->name('rekap.aset');
        Route::get('/rekap-aset/export/excel', [RekapController::class, 'exportAsetExcel'])->name('rekap.aset.export.excel');
        Route::get('/rekap-aset/export/pdf', [RekapController::class, 'exportAsetPdf'])->name('rekap.aset.export.pdf');
        Route::get('/rekap-permintaan-perangkat', [RekapController::class, 'permintaanPerangkat'])->name('rekap.permintaanPerangkat');
        Route::get('/rekap-permintaan-perangkat/export/excel', [RekapController::class, 'exportPermintaanExcel'])->name('rekap.permintaanPerangkat.export.excel');
        Route::get('/rekap-permintaan-perangkat/export/pdf', [RekapController::class, 'exportPermintaanPdf'])->name('rekap.permintaanPerangkat.export.pdf');
        Route::post('/permintaan-perangkat/{permintaanPerangkat}/status', [PermintaanPerangkatController::class, 'updateStatus'])->name('permintaan-perangkat.updateStatus');
        Route::get('/log-history', [LogHistoryController::class, 'index'])->name('log-history.index');
        Route::get('/log-history/export/excel', [LogHistoryController::class, 'exportExcel'])->name('log-history.export.excel');
        Route::get('/log-history/export/pdf', [LogHistoryController::class, 'exportPdf'])->name('log-history.export.pdf');
        Route::get('/aset-edit-requests', [AsetEditRequestController::class, 'index'])->name('aset.editRequests.index');
        Route::get('/aset-edit-requests/export/excel', [AsetEditRequestController::class, 'exportExcel'])->name('aset.editRequests.export.excel');
        Route::get('/aset-edit-requests/export/pdf', [AsetEditRequestController::class, 'exportPdf'])->name('aset.editRequests.export.pdf');
        Route::post('/aset-edit-requests/{editRequest}/approve', [AsetController::class, 'approveEdit'])->name('aset.editRequests.approve');
        Route::post('/aset-edit-requests/{editRequest}/reject', [AsetController::class, 'rejectEdit'])->name('aset.editRequests.reject');
        Route::get('/ukers/export/excel', [UkerController::class, 'exportExcel'])->name('ukers.export.excel');
        Route::get('/ukers/export/pdf', [UkerController::class, 'exportPdf'])->name('ukers.export.pdf');
        Route::resource('ukers', UkerController::class)->except(['show']);
        Route::get('/kode-aset/export/excel', [KodeAsetController::class, 'exportExcel'])->name('kode-aset.export.excel');
        Route::get('/kode-aset/export/pdf', [KodeAsetController::class, 'exportPdf'])->name('kode-aset.export.pdf');
        Route::resource('kode-aset', KodeAsetController::class)->except(['show'])->parameters(['kode-aset' => 'kodeAset']);
        Route::get('/pekerja/export/excel', [PekerjaController::class, 'exportExcel'])->name('pekerja.export.excel');
        Route::get('/pekerja/export/pdf', [PekerjaController::class, 'exportPdf'])->name('pekerja.export.pdf');
        Route::resource('pekerja', PekerjaController::class)->except(['show']);
    });
});

require __DIR__.'/auth.php';
