<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    // Lanjutan dari LoginRequest::authenticate() pas kedapatan sesi lain
    // masih aktif -- token sekali-pakai udah nandain password bener di
    // request SEBELUMNYA, jadi di sini gak perlu re-input password lagi.
    // Begitu dikonfirmasi, semua sesi LAIN milik user itu dihapus dari
    // tabel sessions (otomatis ke-logout di device lama begitu ada request
    // berikutnya di sana), baru login diselesaikan di device ini.
    public function confirmForceLogin(Request $request): RedirectResponse
    {
        $request->validate(['token' => 'required|string']);

        $data = Cache::pull("login-confirm:{$request->input('token')}");

        if (! $data) {
            throw ValidationException::withMessages([
                'pn' => 'Konfirmasi sudah kedaluwarsa, silakan login ulang.',
            ]);
        }

        DB::table('sessions')->where('user_id', $data['user_id'])->delete();

        Auth::loginUsingId($data['user_id']);
        $request->session()->regenerate();
        LoginLog::catat($data['user_id'], Auth::user()->pn, LoginLog::STATUS_BERHASIL, $request);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
