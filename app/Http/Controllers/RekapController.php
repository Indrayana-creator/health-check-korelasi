<?php

namespace App\Http\Controllers;

use App\Models\Aset;
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

        $totalCabang = $rekap->count();
        $avgCompliance = $totalCabang > 0 ? round($rekap->avg('persen'), 1) : 0;
        $totalPerluPerhatian = $rekap->where('status', 'PERLU PERHATIAN')->count();

        $trenCompliance = $this->hitungTrenCompliance($formList);

        return view('rekap.index', compact('rekap', 'totalCabang', 'avgCompliance', 'totalPerluPerhatian', 'trenCompliance'));
    }

    // Tren compliance keseluruhan (semua cabang digabung) per PERIODE, buat
    // dilihat naik/turunnya dari waktu ke waktu. "periode" itu teks bebas
    // ("Juli 2026", dst, bukan format Triwulan tetap) jadi urutan kronologisnya
    // gak bisa diandalkan dari string-nya -- dipakai tanggal_pemeriksaan
    // PALING AWAL di tiap periode buat nentuin urutan tampilnya.
    protected function hitungTrenCompliance($formList): array
    {
        return $formList
            ->groupBy('periode')
            ->map(function ($formsDalamPeriode, $periode) {
                $totalItem = $formsDalamPeriode->sum(fn ($f) => $f->items->count());
                $totalOk = $formsDalamPeriode->sum(fn ($f) => $f->items->where('status', 'OK')->count());

                return [
                    'periode' => $periode,
                    'persen' => $totalItem > 0 ? round($totalOk / $totalItem * 100, 1) : 0,
                    'urutan' => $formsDalamPeriode->min('tanggal_pemeriksaan'),
                ];
            })
            ->sortBy('urutan')
            ->values()
            ->all();
    }

    // Rekap kondisi ASET (bukan health check) di-roll up per CABANG, pola
    // groupby & threshold status-nya sama persis kayak index() di atas biar
    // konsisten -- cuma sumber datanya Aset::kondisi, bukan item checklist.
    public function aset(Request $request)
    {
        $asetList = Aset::with('uker')->get();

        $rekap = $asetList
            ->groupBy(fn ($aset) => $aset->uker?->uker_spv ?? 'Tidak diketahui')
            ->map(function ($asetDalamCabang, $namaCabang) {
                $total = $asetDalamCabang->count();
                $normal = $asetDalamCabang->where('kondisi', 'NORMAL')->count();
                $rusak = $asetDalamCabang->where('kondisi', 'RUSAK')->count();
                $tidakLayak = $asetDalamCabang->where('kondisi', 'TIDAK LAYAK')->count();
                $lainnya = $total - $normal - $rusak - $tidakLayak;
                $persenSehat = $total > 0 ? round($normal / $total * 100, 1) : 0;

                return [
                    'cabang' => $namaCabang,
                    'jumlah_uker_lapor' => $asetDalamCabang->pluck('uker_kode')->unique()->count(),
                    'total' => $total,
                    'normal' => $normal,
                    'rusak' => $rusak,
                    'tidak_layak' => $tidakLayak,
                    'lainnya' => $lainnya,
                    'persen_sehat' => $persenSehat,
                    'status' => $persenSehat >= 95 ? 'SANGAT BAIK' : ($persenSehat >= 80 ? 'BAIK' : 'PERLU PERHATIAN'),
                ];
            })
            ->sortBy('persen_sehat')
            ->values();

        $totalCabang = $rekap->count();
        $avgPersenSehat = $totalCabang > 0 ? round($rekap->avg('persen_sehat'), 1) : 0;
        $totalPerluPerhatian = $rekap->where('status', 'PERLU PERHATIAN')->count();

        // Distribusi kondisi keseluruhan -- ini snapshot KONDISI SAAT INI, bukan
        // tren dari waktu ke waktu, karena tabel aset gak nyimpen riwayat
        // perubahan kondisi (beda sama health check yang emang per-periode).
        $distribusiKondisi = [
            'Normal' => $asetList->where('kondisi', 'NORMAL')->count(),
            'Rusak' => $asetList->where('kondisi', 'RUSAK')->count(),
            'Tidak Layak' => $asetList->where('kondisi', 'TIDAK LAYAK')->count(),
            'Lainnya' => $asetList->whereNotIn('kondisi', ['NORMAL', 'RUSAK', 'TIDAK LAYAK'])->count(),
        ];

        return view('rekap.aset', compact('rekap', 'totalCabang', 'avgPersenSehat', 'totalPerluPerhatian', 'distribusiKondisi'));
    }
}
