<?php

namespace App\Http\Controllers;

use App\Models\Aset;
use App\Models\HealthCheckForm;
use App\Support\ComplianceScale;
use App\Support\PeriodeMingguan;
use Illuminate\Http\Request;

class RekapController extends Controller
{
    // Rekap kepatuhan health check di-roll up per CABANG (uker_spv), bukan per
    // uker/unit satuan -- jadi 1 cabang dengan banyak unit di bawahnya
    // dijumlahkan dulu sebelum dihitung ulang persentasenya. Ini sama persis
    // logikanya dengan prototype Python rekap_health_check.py yang pertama
    // kali kita rancang di awal project.
    //
    // Siklus HC sekarang mingguan, jadi rekap ini ditampilkan 2 versi:
    // "Minggu Ini" (Senin-Jumat berjalan, otomatis kosong lagi begitu ganti
    // minggu -- bukan akumulasi selamanya) dan "Bulan Ini" (gabungan semua
    // minggu dalam bulan berjalan, buat gambaran lebih luas). Chart tren tetap
    // pakai SELURUH histori karena fungsinya emang liat naik-turun dari waktu
    // ke waktu, bukan snapshot periode tertentu.
    public function index(Request $request)
    {
        $formList = HealthCheckForm::with(['uker', 'items'])->get();

        [$awalMinggu, $akhirMinggu] = PeriodeMingguan::rentang(now());
        $awalBulan = now()->copy()->startOfMonth();
        $akhirBulan = now()->copy()->endOfMonth();

        $formMingguIni = $formList->filter(fn ($f) => $f->tanggal_pemeriksaan?->between($awalMinggu, $akhirMinggu));
        $formBulanIni = $formList->filter(fn ($f) => $f->tanggal_pemeriksaan?->between($awalBulan, $akhirBulan));

        $rekapMingguan = $this->rekapPerCabang($formMingguIni);
        $rekapBulanan = $this->rekapPerCabang($formBulanIni);

        $statMingguan = $this->ringkasStat($rekapMingguan);
        $statBulanan = $this->ringkasStat($rekapBulanan);

        $trenCompliance = $this->hitungTrenCompliance($formList);

        $labelMinggu = PeriodeMingguan::label(now());
        $labelBulan = now()->locale('id')->translatedFormat('F Y');

        return view('rekap.index', compact(
            'rekapMingguan', 'rekapBulanan', 'statMingguan', 'statBulanan',
            'trenCompliance', 'labelMinggu', 'labelBulan'
        ));
    }

    // Roll-up per cabang (uker_spv) dari sekumpulan form -- dipakai bareng
    // buat versi mingguan maupun bulanan, cuma beda input $formList-nya.
    protected function rekapPerCabang($formList)
    {
        return $formList
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
                    'status' => ComplianceScale::label($persen),
                ];
            })
            ->sortBy('persen')
            ->values();
    }

    protected function ringkasStat($rekap): array
    {
        $totalCabang = $rekap->count();

        return [
            'total_cabang' => $totalCabang,
            'avg_compliance' => $totalCabang > 0 ? round($rekap->avg('persen'), 1) : 0,
            'total_perlu_perhatian' => $rekap->where('status', 'PERLU PERHATIAN')->count(),
        ];
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
