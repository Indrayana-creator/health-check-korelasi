<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Aset;
use App\Models\HealthCheckForm;
use App\Models\Uker;

// Dipakai bareng oleh DashboardController (buat card ringkasan) & UkerTreeController
// (buat halaman /uker-tree yang render tree lengkap) -- biar logic hitung tree-nya
// gak duplikat di 2 tempat.
trait BuildsUkerTree
{
    private function bangunTreeUker(): ?array
    {
        $semuaUker = Uker::all()->keyBy('kode');

        $jumlahAsetPerUker = Aset::selectRaw('uker_kode, count(*) as jumlah')
            ->groupBy('uker_kode')->pluck('jumlah', 'uker_kode');

        $formPerUker = HealthCheckForm::with('items')->get()->groupBy('uker_kode');
        $complianceePerUker = $formPerUker->map(function ($forms) {
            $total = $forms->sum(fn ($f) => $f->items->count());
            $ok = $forms->sum(fn ($f) => $f->items->where('status', 'OK')->count());
            return $total > 0 ? round($ok / $total * 100, 1) : null;
        });

        $children = [];
        foreach ($semuaUker as $u) {
            if ($u->kode_spv && $u->kode_spv != $u->kode) {
                $children[$u->kode_spv][] = $u->kode;
            }
        }

        $kumpulan = [];
        $hitung = function ($kode) use (&$hitung, &$kumpulan, $children, $jumlahAsetPerUker, $complianceePerUker) {
            if (isset($kumpulan[$kode])) {
                return $kumpulan[$kode];
            }
            $totalAset = $jumlahAsetPerUker->get($kode, 0);
            $complianceList = [];
            if ($complianceePerUker->get($kode) !== null) {
                $complianceList[] = $complianceePerUker->get($kode);
            }
            $jumlahAnak = count($children[$kode] ?? []);

            foreach ($children[$kode] ?? [] as $kodeAnak) {
                $hasilAnak = $hitung($kodeAnak);
                $totalAset += $hasilAnak['total_aset'];
                $complianceList = array_merge($complianceList, $hasilAnak['compliance_list']);
                $jumlahAnak += $hasilAnak['jumlah_unit_bawah'];
            }

            $kumpulan[$kode] = [
                'total_aset' => $totalAset,
                'compliance_list' => $complianceList,
                'rata_compliance' => count($complianceList) ? round(array_sum($complianceList) / count($complianceList), 1) : null,
                'jumlah_unit_bawah' => $jumlahAnak,
            ];
            return $kumpulan[$kode];
        };

        // Mulai dari Kanwil (146) sebagai root
        $hitung(146);

        $bangunNode = function ($kode) use (&$bangunNode, $semuaUker, $children, $kumpulan) {
            $u = $semuaUker[$kode] ?? null;
            if (!$u) {
                return null;
            }
            $anak = collect($children[$kode] ?? [])
                ->map(fn ($k) => $bangunNode($k))
                ->filter()
                ->sortBy('nama')
                ->values();

            return [
                'kode' => $u->kode,
                'nama' => $u->nama,
                'jenis' => $u->jenis,
                'total_aset' => $kumpulan[$kode]['total_aset'] ?? 0,
                'rata_compliance' => $kumpulan[$kode]['rata_compliance'] ?? null,
                'jumlah_unit_bawah' => $kumpulan[$kode]['jumlah_unit_bawah'] ?? 0,
                'anak' => $anak,
            ];
        };

        return $bangunNode(146);
    }
}
