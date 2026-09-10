<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function __construct(protected TwoFactorAuthService $service) {}

    // GET /two-factor/setup -- cuma keliatan buat admin yang belum
    // mfaAktif() (lihat EnsureAdminTwoFactor). Secret di-generate sekali
    // & disimpen di session (BUKAN langsung ke DB) sampai user berhasil
    // konfirmasi kode pertama -- kalau langsung disimpen ke DB, admin
    // yang batal di tengah jalan (refresh/nutup tab) bakal ketinggalan
    // secret yang gak pernah divalidasi discan bener oleh authenticator-nya.
    public function setup(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->mfaAktif()) {
            return redirect()->route('dashboard');
        }

        $secret = $request->session()->get('mfa_setup_secret') ?? $this->service->generateSecretKey();
        $request->session()->put('mfa_setup_secret', $secret);

        $qrCodeDataUri = $this->service->qrCodeDataUri($user, $secret);

        return view('auth.two-factor-setup', compact('secret', 'qrCodeDataUri'));
    }

    // POST /two-factor/setup -- konfirmasi kode pertama dari authenticator
    // app buat mastiin secret-nya bener kesimpen & discan dengan benar,
    // baru abis ini secret dipindah dari session ke DB (terenkripsi) &
    // dianggap resmi aktif.
    public function confirm(Request $request): RedirectResponse
    {
        $user = $request->user();
        $secret = $request->session()->get('mfa_setup_secret');

        if (! $secret) {
            return redirect()->route('two-factor.setup');
        }

        $validated = $request->validate(['kode' => 'required|string']);

        if (! $this->service->verifyKey($secret, $validated['kode'])) {
            return back()->withErrors(['kode' => 'Kode gak cocok. Pastikan waktu di HP Anda akurat, lalu coba lagi.']);
        }

        $recoveryCodes = $this->service->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('mfa_setup_secret');
        $request->session()->put('mfa_terverifikasi', true);
        $request->session()->put('mfa_recovery_codes_baru', $recoveryCodes);

        return redirect()->route('two-factor.recovery-codes');
    }

    // Halaman "simpan kode cadangan ini" -- SEKALI tampil doang, baca dari
    // session (bukan query ulang ke DB) biar gak ada cara nampilin ulang
    // recovery codes plaintext di kemudian hari lewat URL ini.
    public function recoveryCodes(Request $request): View|RedirectResponse
    {
        $codes = $request->session()->pull('mfa_recovery_codes_baru');

        if (! $codes) {
            return redirect()->route('dashboard');
        }

        return view('auth.two-factor-recovery-codes', ['codes' => $codes]);
    }

    // GET /two-factor/challenge -- diminta tiap kali SESI BARU (habis
    // login), bukan tiap request. Lihat EnsureAdminTwoFactor buat gerbangnya.
    public function challenge(Request $request): View|RedirectResponse
    {
        if (! $request->user()->mfaAktif()) {
            return redirect()->route('two-factor.setup');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate(['kode' => 'required|string']);

        if ($this->service->verifyKey($user->two_factor_secret, $validated['kode'])) {
            $request->session()->put('mfa_terverifikasi', true);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Recovery code dipakai gantiin kode authenticator -- format beda
        // (ada strip di tengah, contoh ABCDE-FGHJK) jadi gak akan ke-match
        // verifyKey() di atas walau kebetulan sama-sama string.
        $recoveryCodes = $user->two_factor_recovery_codes ?? [];
        if (in_array($validated['kode'], $recoveryCodes, true)) {
            $recoveryCodesSisa = array_values(array_diff($recoveryCodes, [$validated['kode']]));

            $user->forceFill(['two_factor_recovery_codes' => $recoveryCodesSisa])->save();
            $request->session()->put('mfa_terverifikasi', true);

            return redirect()->intended(route('dashboard', absolute: false))
                ->with('status', 'Kode cadangan berhasil dipakai. Sisa '.count($recoveryCodesSisa).' kode cadangan lagi.');
        }

        return back()->withErrors(['kode' => 'Kode salah atau sudah kedaluwarsa.']);
    }
}
