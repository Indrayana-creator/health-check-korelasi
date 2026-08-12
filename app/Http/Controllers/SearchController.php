<?php

namespace App\Http\Controllers;

use App\Models\Aset;
use App\Models\HealthCheckForm;
use App\Models\Uker;
use Illuminate\Http\Request;

// Pencarian global di topbar -- cari cepat lintas Aset & Health Check dari
// halaman manapun. Scoping RBAC-nya ngikutin pola yang sama persis kayak
// AsetController/HealthCheckController: admin lihat semua, user lihat punya
// uker sendiri + SEMUA turunannya (bukan cuma uker sendiri).
class SearchController extends Controller
{
    const BATAS_HASIL = 5;

    public function search(Request $request)
    {
        $q = trim((string) $request->input('q'));
        if (mb_strlen($q) < 2) {
            return response()->json(['aset' => [], 'healthcheck' => []]);
        }

        $isAdmin = $request->user()->role === 'admin';
        $ukerBolehDiakses = $isAdmin ? [] : Uker::descendantKodes($request->user()->uker_kode);

        $asetQuery = Aset::with(['uker', 'kodeAset'])
            ->where(function ($sub) use ($q) {
                $sub->where('no_asset', 'like', "%{$q}%")
                    ->orWhere('sn', 'like', "%{$q}%")
                    ->orWhere('merek', 'like', "%{$q}%")
                    ->orWhere('tipe_model', 'like', "%{$q}%")
                    ->orWhere('pemegang_nama', 'like', "%{$q}%");
            });
        if (! $isAdmin) {
            $asetQuery->whereIn('uker_kode', $ukerBolehDiakses);
        }
        $asetHasil = $asetQuery->latest()->take(self::BATAS_HASIL)->get()->map(fn ($a) => [
            'id' => $a->id,
            'title' => $a->no_asset,
            'subtitle' => trim("{$a->merek} {$a->tipe_model} · {$a->uker?->nama}"),
            'url' => route('aset.edit', $a),
        ]);

        $hcQuery = HealthCheckForm::with('uker')
            ->where(function ($sub) use ($q) {
                $sub->where('periode', 'like', "%{$q}%")
                    ->orWhereHas('uker', fn ($u) => $u->where('nama', 'like', "%{$q}%"));
            });
        if (! $isAdmin) {
            $hcQuery->whereIn('uker_kode', $ukerBolehDiakses);
        }
        $hcHasil = $hcQuery->latest()->take(self::BATAS_HASIL)->get()->map(fn ($f) => [
            'id' => $f->id,
            'title' => "{$f->periode} · {$f->uker?->nama}",
            'subtitle' => "Status: {$f->status_approval}",
            'url' => route('healthcheck.edit', $f),
        ]);

        return response()->json(['aset' => $asetHasil, 'healthcheck' => $hcHasil]);
    }
}
