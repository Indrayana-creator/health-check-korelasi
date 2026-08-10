<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsUkerTree;
use App\Models\Aset;
use App\Models\HealthCheckForm;
use App\Models\Uker;
use Illuminate\Http\Request;

class UkerTreeController extends Controller
{
    use BuildsUkerTree;

    // Halaman tree lengkap, berdiri sendiri (sebelumnya sempat digabung ke
    // dashboard, tapi di-revert karena bikin dashboard kepanjangan). Dashboard
    // sekarang cuma nampilin ringkasan kecil yang link ke sini.
    public function index(Request $request)
    {
        $tree = $this->bangunTreeUker();

        return view('uker-tree.index', compact('tree'));
    }

    // Dipanggil via AJAX pas tombol "Detail" diklik -- hitung rekap khusus
    // buat 1 node + semua anak-cucunya, cuma pas dibutuhkan (bukan pas load
    // halaman tree pertama kali, biar tetap ringan)
    public function detail(Request $request, int $kode)
    {
        $semuaUker = Uker::all()->keyBy('kode');
        $node = $semuaUker->get($kode);
        if (! $node) {
            abort(404);
        }

        $childrenMap = [];
        foreach ($semuaUker as $u) {
            if ($u->kode_spv && $u->kode_spv != $u->kode) {
                $childrenMap[$u->kode_spv][] = $u->kode;
            }
        }

        // Kumpulkan kode dirinya sendiri + semua keturunannya
        $kumpulanKode = [];
        $stack = [$kode];
        while ($stack) {
            $k = array_pop($stack);
            $kumpulanKode[] = $k;
            foreach ($childrenMap[$k] ?? [] as $anak) {
                $stack[] = $anak;
            }
        }

        $distribusiPerangkat = Aset::whereIn('uker_kode', $kumpulanKode)
            ->join('kode_aset', 'aset.kode_aset_kode', '=', 'kode_aset.kode')
            ->selectRaw('kode_aset.kategori as label, count(*) as jumlah')
            ->groupBy('kode_aset.kategori')
            ->orderByDesc('jumlah')
            ->get();

        $distribusiKondisi = Aset::whereIn('uker_kode', $kumpulanKode)
            ->selectRaw('COALESCE(kondisi, "Belum Diisi") as label, count(*) as jumlah')
            ->groupBy('label')
            ->orderByDesc('jumlah')
            ->get();

        $totalAset = Aset::whereIn('uker_kode', $kumpulanKode)->count();
        $jumlahUnitAdaAset = Aset::whereIn('uker_kode', $kumpulanKode)->distinct('uker_kode')->count('uker_kode');

        $forms = HealthCheckForm::with('items')->whereIn('uker_kode', $kumpulanKode)->get();
        $totalItem = $forms->sum(fn ($f) => $f->items->count());
        $totalOk = $forms->sum(fn ($f) => $f->items->where('status', 'OK')->count());
        $rataCompliance = $totalItem > 0 ? round($totalOk / $totalItem * 100, 1) : null;

        return response()->json([
            'nama' => $node->nama,
            'jenis' => $node->jenis,
            'total_aset' => $totalAset,
            'rata_compliance' => $rataCompliance,
            'jumlah_form_hc' => $forms->count(),
            'jumlah_unit_total' => count($kumpulanKode),
            'jumlah_unit_ada_aset' => $jumlahUnitAdaAset,
            'distribusi_perangkat' => $distribusiPerangkat,
            'distribusi_kondisi' => $distribusiKondisi,
        ]);
    }

    // Modal kedua, khusus fokus Health Check -- dipanggil pas badge compliance
    // diklik. Beda dari detail() di atas yang fokusnya ke Data Aset.
    public function complianceDetail(Request $request, int $kode)
    {
        $semuaUker = Uker::all()->keyBy('kode');
        $node = $semuaUker->get($kode);
        if (! $node) {
            abort(404);
        }

        $childrenMap = [];
        foreach ($semuaUker as $u) {
            if ($u->kode_spv && $u->kode_spv != $u->kode) {
                $childrenMap[$u->kode_spv][] = $u->kode;
            }
        }

        $kumpulanKode = [];
        $stack = [$kode];
        while ($stack) {
            $k = array_pop($stack);
            $kumpulanKode[] = $k;
            foreach ($childrenMap[$k] ?? [] as $anak) {
                $stack[] = $anak;
            }
        }

        $forms = HealthCheckForm::with('items')->whereIn('uker_kode', $kumpulanKode)->get();

        // Breakdown compliance per kategori checklist (A-D)
        $semuaItems = $forms->flatMap(fn ($f) => $f->items);
        $perKategori = $semuaItems->groupBy('kategori')->map(function ($items, $kategori) {
            $total = $items->count();
            $ok = $items->where('status', 'OK')->count();

            return [
                'label' => $kategori,
                'total' => $total,
                'ok' => $ok,
                'persen' => $total ? round($ok / $total * 100, 1) : 0,
            ];
        })->values();

        // Breakdown status approval & tindak lanjut (di level form, bukan item)
        $statusApproval = $forms->groupBy('status_approval')->map->count()
            ->map(fn ($jumlah, $label) => ['label' => $label, 'jumlah' => $jumlah])->values();
        $statusTindakLanjut = $forms->groupBy('status_tindak_lanjut')->map->count()
            ->map(fn ($jumlah, $label) => ['label' => $label, 'jumlah' => $jumlah])->values();

        $totalItem = $semuaItems->count();
        $totalOk = $semuaItems->where('status', 'OK')->count();
        $rataCompliance = $totalItem > 0 ? round($totalOk / $totalItem * 100, 1) : null;

        return response()->json([
            'nama' => $node->nama,
            'jenis' => $node->jenis,
            'jumlah_form' => $forms->count(),
            'jumlah_unit_total' => count($kumpulanKode),
            'jumlah_unit_ada_form' => $forms->pluck('uker_kode')->unique()->count(),
            'rata_compliance' => $rataCompliance,
            'per_kategori' => $perKategori,
            'status_approval' => $statusApproval,
            'status_tindak_lanjut' => $statusTindakLanjut,
        ]);
    }
}
