<?php

namespace App\Http\Controllers;

use App\Models\HealthCheckForm;
use Illuminate\Http\Request;

class RekapController extends Controller
{
    // Rekap kepatuhan health check di-roll up per CABANG (uker_spv), bukan per
    // uker/unit satuan -- jadi 1 cabang dengan banyak unit di bawahnya
    // dijumlahkan dulu sebelum dihitung ulang persentasenya. Ini sama persis
    // logikanya dengan prototype Python rekap_health_check.py yang pertama
    // kali kita rancang di awal project.
    public function index(Request $request)
    {
        $formList = HealthCheckForm::with(['uker', 'items'])->get();

        $rekap = $formList
            ->groupBy(fn ($form) => $form->uker?->uker_spv ?? 'Tidak diketahui')
            ->map(function ($formsDalamCabang, $namaCabang) {
                $totalItem = $formsDalamCabang->sum(fn ($f) => $f->items->count());
                $totalOk = $formsDalamCabang->sum(fn ($f) => $f->items->where('status', 'OK')->count());
                $totalNotOk = $formsDalamCabang->sum(fn ($f) => $f->items->where('status', 'Not OK')->count());
                $totalNa = $formsDalamCabang->sum(fn ($f) => $f->items->where('status', 'N/A')->count());
                $totalBelum = $formsDalamCabang->sum(fn ($f) => $f->items->where('status', 'Belum Diperiksa')->count());
                $persen = $totalItem > 0 ? round($totalOk / $totalItem * 100, 1) : 0;

                return [
                    'cabang' => $namaCabang,
                    'jumlah_uker_lapor' => $formsDalamCabang->pluck('uker_kode')->unique()->count(),
                    'total_item' => $totalItem,
                    'ok' => $totalOk,
                    'not_ok' => $totalNotOk,
                    'na' => $totalNa,
                    'belum' => $totalBelum,
                    'persen' => $persen,
                    'status' => $persen >= 95 ? 'SANGAT BAIK' : ($persen >= 80 ? 'BAIK' : 'PERLU PERHATIAN'),
                ];
            })
            ->sortBy('persen')
            ->values();

        return view('rekap.index', compact('rekap'));
    }
}
