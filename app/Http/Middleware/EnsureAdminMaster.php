<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminMaster
{
    /**
     * Pakai di route: ->middleware('admin.master') -- sudah nyakup
     * "harus admin" sekaligus, gak perlu ditumpuk sama middleware('role:admin').
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isAdminMaster()) {
            abort(403, 'Cuma Admin Master yang punya akses ke halaman ini.');
        }

        return $next($request);
    }
}
