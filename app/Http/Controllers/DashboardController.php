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
        if (!$isAdmin) {
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
            $editRequestsMenunggu = \App\Models\AsetEditRequest::with(['aset.uker', 'requester'])
                ->where('status', 'Menunggu')
                ->latest()
                ->take(5)
                ->get();

            $rankingCabang = HealthCheckForm::with(['uker', 'items'])
                ->get()
                ->map(function ($form) {
                    return [
                        'uker' => $form->uker?->nama,
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
            $editRequestsSaya = \App\Models\AsetEditRequest::with('aset')
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

        // ===== 5. Struktur Organisasi (Tree) -- khusus admin =====
        // Dipindah dari halaman /uker-tree yang berdiri sendiri, gabung ke
        // dashboard karena isinya sama-sama rekap. Logic sama persis dengan
        // UkerTreeController::index(), cuma dieksekusi di sini sekarang.
        $tree = null;
        if ($isAdmin) {
            $tree = $this->bangunTreeUker();
        }

        return view('dashboard', compact(
            'totalAset', 'totalFormHc', 'rataCompliance',
            'rankingCabang', 'ukerBelumMengisi', 'ukerBelumAdaAset', 'editRequestsMenunggu', 'editRequestsSaya', 'distribusiPerangkat', 'aktivitasTerbaru', 'isAdmin', 'tree'
        ));
    }

    // Sama seperti logic asli di UkerTreeController::index() -- disalin ke
    // sini karena tree-nya sekarang ditampilkan di dashboard, bukan halaman
    // tersendiri lagi. Endpoint detail() & complianceDetail() (buat modal
    // AJAX) tetap ada di UkerTreeController, gak perlu dipindah.
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
