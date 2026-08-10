<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsUkerTree;
use App\Models\Aset;
use App\Models\AsetEditRequest;
use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\Uker;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use BuildsUkerTree;

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
        $editRequestsMenunggu = collect();
        $editRequestsSaya = collect();
        if ($isAdmin) {
            $editRequestsMenunggu = AsetEditRequest::with(['aset.uker', 'requester'])
                ->where('status', 'Menunggu')
                ->latest()
                ->take(5)
                ->get();

            $rankingCabang = HealthCheckForm::with(['uker', 'items'])
                ->get()
                ->map(function ($form) {
                    return [
                        'uker' => $form->uker?->nama,
                        'kode' => $form->uker_kode,
                        'periode' => $form->periode,
                        'persen' => $form->persenCompliance(),
                        'status_tindak_lanjut' => $form->status_tindak_lanjut,
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
        } else {
            // User biasa: nampilin riwayat permintaan edit aset dia sendiri,
            // biar tau statusnya tanpa harus buka satu-satu tiap aset
            $editRequestsSaya = AsetEditRequest::with('aset')
                ->where('requested_by', $request->user()->id)
                ->latest()
                ->take(5)
                ->get();
        }

        // ===== 3. Distribusi aset per kategori (Personal Computer, Notebook, UPS, dst) =====
        $distribusiPerangkat = (clone $asetQuery)
            ->join('kode_aset', 'aset.kode_aset_kode', '=', 'kode_aset.kode')
            ->selectRaw('kode_aset.kategori as perangkat, count(*) as jumlah')
            ->groupBy('kode_aset.kategori')
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

        // ===== 5. Struktur Organisasi -- khusus admin =====
        // Halaman tree lengkap sekarang balik jadi halaman sendiri (/uker-tree,
        // lihat UkerTreeController::index()). Di dashboard cuma dipakai buat
        // card ringkasan (ambil root node-nya aja: jumlah_unit_bawah & rata_compliance),
        // bukan nge-render seluruh tree lagi. bangunTreeUker() dipakai bareng lewat
        // trait BuildsUkerTree biar gak duplikat logicnya di 2 controller.
        $tree = null;
        $totalKendalaAktif = null;
        if ($isAdmin) {
            $tree = $this->bangunTreeUker();

            // Item checklist "Not OK" yang belum selesai ditindaklanjuti --
            // ringkasan kecil doang, detailnya di halaman Monitoring Kendala.
            $totalKendalaAktif = HealthCheckItem::where('status', 'Not OK')
                ->where('status_tindak_lanjut', '!=', 'Selesai Diperbaiki')
                ->count();
        }

        return view('dashboard', compact(
            'totalAset', 'totalFormHc', 'rataCompliance',
            'rankingCabang', 'ukerBelumMengisi', 'ukerBelumAdaAset', 'editRequestsMenunggu', 'editRequestsSaya', 'distribusiPerangkat', 'aktivitasTerbaru', 'isAdmin', 'tree', 'totalKendalaAktif'
        ));
    }
}
