<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Global (masuk grup middleware 'web') -- sengaja gak ditaruh per-route,
// biar gak ada satu pun halaman admin yang kelewat digerbang gara-gara
// route baru lupa ditambahin middleware ini. No-op total buat guest &
// user non-admin.
class EnsureAdminTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->wajibMfa()) {
            return $next($request);
        }

        // Rute MFA sendiri (setup/challenge) & logout WAJIB dikecualikan,
        // kalau gak bakal infinite redirect loop ke dirinya sendiri.
        if ($request->routeIs('two-factor.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        if (! $user->mfaAktif()) {
            return redirect()->route('two-factor.setup');
        }

        if (! $request->session()->get('mfa_terverifikasi')) {
            return redirect()->route('two-factor.challenge');
        }

        return $next($request);
    }
}
