<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsUkerTree;
use App\Models\Aset;
use App\Models\AsetEditRequest;
use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\PermintaanPerangkat;
use App\Models\Uker;
use App\Support\ComplianceScale;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use BuildsUkerTree;

    public function index(Request $request)
    {
        $isAdmin = $request->user()->role === 'admin';
        $ukerKode = $request->user()->uker_kode;

        // ===== 1. KPI ringkas =====
        // Non-admin di-scope ke uker sendiri + SEMUA turunannya (bukan cuma
        // uker sendiri) -- mirip cara bangunTreeUker() ngumpulin akumulasi
        // buat admin, tapi root-nya uker_kode milik user, bukan Kanwil.
        $asetQuery = Aset::query();
        $formQuery = HealthCheckForm::query()->with('items');
        if (! $isAdmin) {
            $ukerBolehDiakses = Uker::descendantKodes($ukerKode);
            $asetQuery->whereIn('uker_kode', $ukerBolehDiakses);
            $formQuery->whereIn('uker_kode', $ukerBolehDiakses);
        }

        $totalAset = $asetQuery->count();
        $formList = $formQuery->get();
        $totalFormHc = $formList->count();

        $totalItem = $formList->sum(fn ($f) => $f->items->count());
        $totalOk = $formList->sum(fn ($f) => $f->items->where('status', 'OK')->count());
        $rataCompliance = $totalItem > 0 ? round($totalOk / $totalItem * 100, 1) : 0;

        // ===== 1b. Kesehatan Checklist per Kategori (A-D + E) =====
        // Ambil form TERBARU per uker (bukan seluruh histori) dari $formList
        // yang sama (udah di-scope RBAC di atas) -- biar uker yang rajin isi
        // tiap minggu gak "menang banyak" dibanding uker yang baru isi sekali.
        $formTerbaruPerUker = $formList->groupBy('uker_kode')
            ->map(fn ($forms) => $forms->sortByDesc('tanggal_pemeriksaan')->first());

        $semuaItemTerbaru = $formTerbaruPerUker->flatMap(fn ($f) => $f->items);

        $kesehatanPerKategori = collect(config('health_check_checklist'))->keys()->map(function ($kategori) use ($semuaItemTerbaru) {
            $items = $semuaItemTerbaru->where('kategori', $kategori);
            $total = $items->count();
            $ok = $items->where('status', 'OK')->count();
            $persen = $total > 0 ? round($ok / $total * 100, 1) : 0;

            return [
                'kategori' => $kategori,
                'persen' => $persen,
                'label' => ComplianceScale::label($persen),
                'warna' => ComplianceScale::badgeColor($persen),
                'breakdown' => [
                    'OK' => $items->where('status', 'OK')->count(),
                    'Not OK' => $items->where('status', 'Not OK')->count(),
                    'N/A' => $items->where('status', 'N/A')->count(),
                    'Belum Diperiksa' => $items->where('status', 'Belum Diperiksa')->count(),
                ],
            ];
        })->values();

        // Kategori E "Dokumentasi Visual" -- TERPISAH, cuma link foto (bukan
        // checklist OK/Not OK), gak pernah ikut dihitung ke compliance manapun.
        $totalFormTerbaru = $formTerbaruPerUker->count();
        $formLengkapDokumentasi = $formTerbaruPerUker->filter(fn ($f) => $f->jumlahFotoDokumentasiTerisi() === 3)->count();

        // ===== 2. Ranking cabang paling butuh perhatian (khusus admin) =====
        $rankingCabang = collect();
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
        } else {
            // User biasa: nampilin riwayat permintaan edit aset dia sendiri,
            // biar tau statusnya tanpa harus buka satu-satu tiap aset
            $editRequestsSaya = AsetEditRequest::with('aset')
                ->where('requested_by', $request->user()->id)
                ->latest()
                ->take(5)
                ->get();
        }

        // "Belum Isi HC" & "Belum Ada Aset" -- berlaku buat SEMUA role, cuma
        // cakupan uker-nya beda: admin dari semua uker, non-admin cuma dari
        // subtree sendiri (Uker::descendantKodes(), reuse yang sama dipakai
        // di tempat lain, bukan scoping baru).
        $ukerCekKelengkapan = $isAdmin ? Uker::query() : Uker::whereIn('kode', Uker::descendantKodes($ukerKode));

        // Uker (dalam cakupan) yang belum pernah mengisi form Health Check sama sekali
        $kodeUkerSudahIsi = $formList->pluck('uker_kode')->unique();
        $ukerBelumMengisi = (clone $ukerCekKelengkapan)->whereNotIn('kode', $kodeUkerSudahIsi)
            ->orderBy('nama')
            ->get();

        // Uker (dalam cakupan) yang sama sekali belum ada data asetnya
        $kodeUkerAdaAset = (clone $asetQuery)->pluck('uker_kode')->unique();
        $ukerBelumAdaAset = (clone $ukerCekKelengkapan)->whereNotIn('kode', $kodeUkerAdaAset)
            ->orderBy('nama')
            ->get();

        // ===== 3. Distribusi aset per kategori (Personal Computer, Notebook, UPS, dst) =====
        $distribusiPerangkat = (clone $asetQuery)
            ->join('kode_aset', 'aset.kode_aset_kode', '=', 'kode_aset.kode')
            ->selectRaw('kode_aset.kategori as perangkat, count(*) as jumlah')
            ->groupBy('kode_aset.kategori')
            ->orderByDesc('jumlah')
            ->get();

        // ===== 4. Aktivitas terbaru -- dipersempit ke event yang BUTUH
        // PERHATIAN/TINDAKAN admin (submit approval HC, item baru Not OK,
        // ajuan Permintaan Perangkat, ajuan Edit Aset), BUKAN aktivitas
        // rutin (aset ditambahkan, form HC kosong baru dibuat) yang
        // volumenya tinggi tapi gak actionable buat dipantau tiap hari.
        $aktivitasSubmitHc = (clone $formQuery)->where('status_approval', 'Menunggu Approval')
            ->with('uker')->orderByDesc('updated_at')->take(5)->get()->map(fn ($f) => [
                'jenis' => 'Health Check',
                'teks' => "Form health check {$f->periode} ({$f->uker?->nama}) disubmit untuk approval",
                'waktu' => $f->updated_at,
            ]);

        // Reuse $formList yang udah di-scope RBAC di atas (bukan query baru)
        // -- gak ada kolom terpisah yang nyatet KAPAN status berubah jadi
        // Not OK (item di-update in-place), jadi updated_at item dipakai
        // sebagai proxy praktis "baru ketemu masalah".
        $aktivitasNotOk = $formList
            ->flatMap(fn ($f) => $f->items->where('status', 'Not OK')->map(fn ($i) => ['item' => $i, 'form' => $f]))
            ->sortByDesc(fn ($p) => $p['item']->updated_at)
            ->take(5)
            ->map(fn ($p) => [
                'jenis' => 'Kendala',
                'teks' => "Item '{$p['item']->item_pemeriksaan}' di {$p['form']->uker?->nama} berstatus Not OK",
                'waktu' => $p['item']->updated_at,
            ]);

        // Permintaan Perangkat -- scoping exact-match uker sendiri buat
        // non-admin, sama persis kayak PermintaanPerangkatController (bukan
        // subtree, karena yang ngajuin cuma level KC/Cabang).
        $aktivitasPermintaan = ($isAdmin ? PermintaanPerangkat::query() : PermintaanPerangkat::where('uker_kode', $ukerKode))
            ->with('uker')->latest()->take(5)->get()->map(fn ($p) => [
                'jenis' => 'Permintaan Perangkat',
                'teks' => "Permintaan perangkat {$p->no_nota_dinas} diajukan oleh {$p->uker?->nama}",
                'waktu' => $p->created_at,
            ]);

        // Reuse $editRequestsMenunggu/$editRequestsSaya yang udah dihitung
        // di section 2 di atas -- gak query ulang.
        $aktivitasEditAset = ($isAdmin ? $editRequestsMenunggu : $editRequestsSaya)->map(fn ($r) => [
            'jenis' => 'Edit Aset',
            'teks' => "Permintaan edit aset {$r->aset?->no_asset} diajukan",
            'waktu' => $r->created_at,
        ]);

        $aktivitasTerbaru = $aktivitasSubmitHc->concat($aktivitasNotOk)->concat($aktivitasPermintaan)->concat($aktivitasEditAset)
            ->sortByDesc('waktu')->take(8)->values();

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
            'totalAset', 'totalFormHc', 'rataCompliance', 'kesehatanPerKategori', 'totalFormTerbaru', 'formLengkapDokumentasi',
            'rankingCabang', 'ukerBelumMengisi', 'ukerBelumAdaAset', 'editRequestsMenunggu', 'editRequestsSaya', 'distribusiPerangkat', 'aktivitasTerbaru', 'isAdmin', 'tree', 'totalKendalaAktif'
        ));
    }
}
