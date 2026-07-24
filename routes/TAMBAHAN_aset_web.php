// Tambahin baris ini ke routes/web.php, di dalam middleware auth
// (biasanya udah ada group Route::middleware('auth')->group(function () {...})
// dari Breeze, taruh baris ini di dalamnya. Kalau belum ada, taruh persis
// seperti contoh di bawah)

use App\Http\Controllers\AsetController;

Route::middleware('auth')->group(function () {
    Route::resource('aset', AsetController::class)->except(['show']);
});
