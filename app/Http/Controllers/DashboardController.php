<?php

namespace App\Http\Controllers;

use App\Models\Aset;
use App\Models\HealthCheckForm;
use App\Models\Uker;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = $request->user()->role === 'admin';
        $ukerKode = $request->user()->uker_kode;

        // ===== 1. KPI ringkas =====
        $asetQuery = Aset::query();
        $formQuery = HealthCheckForm::query()->with('items');
        if (! $isAdmin) {
            $asetQuery->where('uker_kode', $ukerKode);
            $formQuery->where('uker_kode', $ukerKode);
        }

        $totalAset = $asetQuery->count();
        $formList = $formQuery->get();
        $totalFormHc = $formList->count();

        $totalItem = $formList->sum(fn ($f) => $f->items->count());
        $totalOk = $formList->sum(fn ($f) => $f->items->where('status', 'OK')->count());
        $rataCompliance = $totalItem > 0 ? round($totalOk / $totalItem * 100, 1) : 0;

        // ===== 2. Ranking cabang paling butuh perhatian (khusus admin) =====
        $rankingCabang = collect();
        $ukerBelumMengisi = collect();
        $ukerBelumAdaAset = collect();
        if ($isAdmin) {
            $rankingCabang = HealthCheckForm::with(['uker', 'items'])
                ->get()
                ->map(function ($form) {
                    return [
                        'uker' => $form->uker?->nama,
                        'periode' => $form->periode,
                        'persen' => $form->persenCompliance(),
                    ];
                })
                ->sortBy('persen')
                ->take(5)
                ->values();

            // Uker yang belum pernah mengisi form Health Check sama sekali
            $kodeUkerSudahIsi = HealthCheckForm::pluck('uker_kode')->unique();
            $ukerBelumMengisi = Uker::whereNotIn('kode', $kodeUkerSudahIsi)
                ->orderBy('nama')
                ->get();

            // Uker yang sama sekali belum ada data asetnya
            $kodeUkerAdaAset = Aset::pluck('uker_kode')->unique();
            $ukerBelumAdaAset = Uker::whereNotIn('kode', $kodeUkerAdaAset)
                ->orderBy('nama')
                ->get();
        }

        // ===== 3. Distribusi aset per tipe perangkat =====
        $distribusiPerangkat = (clone $asetQuery)
            ->join('kode_aset', 'aset.kode_aset_kode', '=', 'kode_aset.kode')
            ->selectRaw('kode_aset.nama as perangkat, count(*) as jumlah')
            ->groupBy('kode_aset.nama')
            ->orderByDesc('jumlah')
            ->get();

        // ===== 4. Aktivitas terbaru (gabungan aset + health check, 8 terbaru) =====
        $aktivitasAset = (clone $asetQuery)->with(['uker', 'kodeAset'])->latest()->take(5)->get()->map(function ($a) {
            return [
                'jenis' => 'Aset',
                'teks' => "{$a->kodeAset?->nama} ({$a->merek} {$a->tipe_model}) ditambahkan ke {$a->uker?->nama}",
                'waktu' => $a->created_at,
            ];
        });
        $aktivitasHc = (clone $formQuery)->with('uker')->latest()->take(5)->get()->map(function ($f) {
            return [
                'jenis' => 'Health Check',
                'teks' => "Form health check {$f->periode} dibuat untuk {$f->uker?->nama}",
                'waktu' => $f->created_at,
            ];
        });
        $aktivitasTerbaru = $aktivitasAset->concat($aktivitasHc)->sortByDesc('waktu')->take(8)->values();

        return view('dashboard', compact(
            'totalAset', 'totalFormHc', 'rataCompliance',
            'rankingCabang', 'ukerBelumMengisi', 'ukerBelumAdaAset', 'distribusiPerangkat', 'aktivitasTerbaru', 'isAdmin'
        ));
    }
}
