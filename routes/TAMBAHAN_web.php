// Tambahin baris-baris ini ke file routes/web.php yang udah ada
// (jangan replace seluruh file, cukup tambahin di bagian bawah)

use App\Http\Controllers\ImportController;

Route::get('/import', [ImportController::class, 'form']);
Route::post('/import/pekerja', [ImportController::class, 'pekerja'])->name('import.pekerja');
Route::post('/import/petugas-it', [ImportController::class, 'petugasIt'])->name('import.petugasIt');
