<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsUkerTree;
use App\Models\Aset;
use App\Models\AsetEditRequest;
use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\PermintaanPerangkat;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\ReminderInputAset;
use App\Notifications\ReminderPengisianHealthCheck;
use App\Support\ComplianceScale;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use BuildsUkerTree;

    // Reuse yang sama dipakai index() buat isi chart "Kesehatan Checklist per
    // Kategori" -- form TERBARU per uker (bukan seluruh histori), di-scope RBAC
    // (admin: semua, non-admin: uker sendiri + turunan). Dipakai juga oleh
    // itemDetail() biar angka di modal drill-down PERSIS sama sumbernya
    // kayak angka yang ditampilin di chart, gak ada versi lain yang beda.
    protected function formTerbaruPerUkerScoped(Request $request)
    {
        $isAdmin = $request->user()->role === 'admin';
        $formQuery = HealthCheckForm::query()->with('items.form.uker');
        if (! $isAdmin) {
            $ukerBolehDiakses = Uker::descendantKodes($request->user()->uker_kode);
            $formQuery->whereIn('uker_kode', $ukerBolehDiakses);
        }

        return $formQuery->get()->groupBy('uker_kode')
            ->map(fn ($forms) => $forms->sortByDesc('tanggal_pemeriksaan')->first());
    }

    // Data buat modal drill-down chart per kategori di Dashboard -- daftar
    // item checklist (dari form terbaru tiap uker, sama kayak chart-nya)
    // yang match kategori + status yang diklik.
    public function itemDetail(Request $request)
    {
        $validated = $request->validate([
            'kategori' => 'required|string',
            'status' => 'required|in:OK,Not OK,N/A,Belum Diperiksa',
        ]);

        $items = $this->formTerbaruPerUkerScoped($request)
            ->flatMap(fn ($f) => $f->items)
            ->where('kategori', $validated['kategori'])
            ->where('status', $validated['status'])
            ->sortBy(fn ($i) => $i->form?->uker?->nama)
            ->values()
            ->map(fn ($i) => [
                'form_id' => $i->form?->id,
                'uker' => $i->form?->uker?->nama,
                'periode' => $i->form?->periode,
                'item_pemeriksaan' => $i->item_pemeriksaan,
                'catatan' => $i->catatan,
                'status_tindak_lanjut' => $i->status_tindak_lanjut,
            ]);

        return response()->json(['items' => $items]);
    }

    // Boleh kirim pengingat ke suatu uker kalau ADMIN (bebas semua uker) atau
    // uker itu ada di dalam subtree sendiri (sama kayak cakupan yang dipakai
    // buat nampilin daftar "Belum Isi HC"/"Belum Ada Aset" di Dashboard --
    // gak masuk akal ngirim pengingat ke uker yang bahkan gak kelihatan
    // di daftar sendiri).
    protected function authorizeKirimPengingat(Request $request, Uker $uker): void
    {
        $isAdmin = $request->user()->role === 'admin';
        if ($isAdmin) {
            return;
        }

        abort_unless(in_array($uker->kode, Uker::descendantKodes($request->user()->uker_kode)), 403);
    }

    public function kirimPengingatHc(Request $request, Uker $uker)
    {
        $this->authorizeKirimPengingat($request, $uker);

        $users = User::where('uker_kode', $uker->kode)->where('is_active', true)->get();
        abort_if($users->isEmpty(), 422, 'Uker ini belum punya akun user aktif buat dikirimin notifikasi.');

        $users->each->notify(new ReminderPengisianHealthCheck($uker));

        return back()->with('status', "Pengingat pengisian Health Check terkirim ke {$uker->nama}.");
    }

    public function kirimPengingatAset(Request $request, Uker $uker)
    {
        $this->authorizeKirimPengingat($request, $uker);

        $users = User::where('uker_kode', $uker->kode)->where('is_active', true)->get();
        abort_if($users->isEmpty(), 422, 'Uker ini belum punya akun user aktif buat dikirimin notifikasi.');

        $users->each->notify(new ReminderInputAset($uker));

        return back()->with('status', "Pengingat input data aset terkirim ke {$uker->nama}.");
    }

    public function index(Request $request)
    {
        $isAdmin = $request->user()->role === 'admin';
        $ukerKode = $request->user()->uker_kode;
        $ukerBolehDiakses = $isAdmin ? [] : Uker::descendantKodes($ukerKode);

        // ===== 0. Perlu Tindakan Anda =====
        // Sebelumnya hal yang "butuh tindakan" tersebar di 4 halaman terpisah
        // (HC menunggu approval, Kendala belum ditindaklanjuti, Permintaan
        // Perangkat pending, Permintaan Edit Aset menunggu) tanpa satu titik
        // kumpul -- widget ini narik angka dari keempatnya sekaligus (admin)
        // atau dari yang relevan buat cabang (user), masing-masing langsung
        // link ke halaman terkait dengan filter status yang sudah diterapkan.
        $aksiPerlu = collect();
        if ($isAdmin) {
            $hcMenunggu = HealthCheckForm::where('status_approval', 'Menunggu Approval')->count();
            if ($hcMenunggu > 0) {
                $aksiPerlu->push([
                    'label' => 'Health Check menunggu approval',
                    'jumlah' => $hcMenunggu,
                    'href' => route('healthcheck.index', ['status_approval' => 'Menunggu Approval']),
                    'icon' => 'M9 12l2 2 4-4M5 6h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z',
                ]);
            }

            $kendalaBelum = HealthCheckItem::where('status', 'Not OK')->where('status_tindak_lanjut', 'Belum Ditindaklanjuti')->count();
            if ($kendalaBelum > 0) {
                $aksiPerlu->push([
                    'label' => 'Kendala belum ditindaklanjuti',
                    'jumlah' => $kendalaBelum,
                    'href' => route('monitoring.index', ['status_tindak_lanjut' => 'Belum Ditindaklanjuti']),
                    'icon' => 'M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z',
                ]);
            }

            // Terpisah dari "Belum Ditindaklanjuti" -- ini item yang UDAH mulai
            // dikerjakan ("Sedang Diproses") tapi kelamaan (>7 hari), gak
            // kehitung sama sekali di angka manapun sebelum ini padahal
            // justru paling butuh perhatian ulang.
            $kendalaMelewatiSla = HealthCheckItem::where('status', 'Not OK')
                ->where('status_tindak_lanjut', 'Sedang Diproses')
                ->whereNotNull('mulai_diproses_at')
                ->get()
                ->filter(fn ($item) => MonitoringController::itemMelewatiSlaDiproses($item))
                ->count();
            if ($kendalaMelewatiSla > 0) {
                $aksiPerlu->push([
                    'label' => 'Kendala sedang diproses tapi melewati SLA',
                    'jumlah' => $kendalaMelewatiSla,
                    'href' => route('monitoring.index', ['melewati_sla' => 1]),
                    'icon' => 'M12 8v4l3 3M12 22a10 10 0 100-20 10 10 0 000 20z',
                ]);
            }

            $permintaanPending = PermintaanPerangkat::where('status', '!=', 'Done Terkirim')->count();
            if ($permintaanPending > 0) {
                $aksiPerlu->push([
                    'label' => 'Permintaan perangkat belum selesai',
                    'jumlah' => $permintaanPending,
                    'href' => route('permintaan-perangkat.index'),
                    'icon' => 'M20 7h-9M14 17H5M17 3l3 4-3 4M7 21l-3-4 3-4',
                ]);
            }

            $editAsetMenunggu = AsetEditRequest::where('status', 'Menunggu')->count();
            if ($editAsetMenunggu > 0) {
                $aksiPerlu->push([
                    'label' => 'Permintaan edit aset menunggu approval',
                    'jumlah' => $editAsetMenunggu,
                    'href' => route('aset.editRequests.index'),
                    'icon' => 'M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z',
                ]);
            }
        } else {
            $kendalaBelumSaya = HealthCheckItem::whereHas('form', fn ($q) => $q->whereIn('uker_kode', $ukerBolehDiakses))
                ->where('status', 'Not OK')->where('status_tindak_lanjut', 'Belum Ditindaklanjuti')->count();
            if ($kendalaBelumSaya > 0) {
                $aksiPerlu->push([
                    'label' => 'Kendala di uker Anda belum ditindaklanjuti',
                    'jumlah' => $kendalaBelumSaya,
                    'href' => route('monitoring.index', ['status_tindak_lanjut' => 'Belum Ditindaklanjuti']),
                    'icon' => 'M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z',
                ]);
            }

            $kendalaMelewatiSlaSaya = HealthCheckItem::whereHas('form', fn ($q) => $q->whereIn('uker_kode', $ukerBolehDiakses))
                ->where('status', 'Not OK')
                ->where('status_tindak_lanjut', 'Sedang Diproses')
                ->whereNotNull('mulai_diproses_at')
                ->get()
                ->filter(fn ($item) => MonitoringController::itemMelewatiSlaDiproses($item))
                ->count();
            if ($kendalaMelewatiSlaSaya > 0) {
                $aksiPerlu->push([
                    'label' => 'Kendala di uker Anda sedang diproses tapi melewati SLA',
                    'jumlah' => $kendalaMelewatiSlaSaya,
                    'href' => route('monitoring.index', ['melewati_sla' => 1]),
                    'icon' => 'M12 8v4l3 3M12 22a10 10 0 100-20 10 10 0 000 20z',
                ]);
            }

            $hcDitolakSaya = HealthCheckForm::whereIn('uker_kode', $ukerBolehDiakses)->where('status_approval', 'Ditolak')->count();
            if ($hcDitolakSaya > 0) {
                $aksiPerlu->push([
                    'label' => 'Health Check ditolak, perlu direvisi',
                    'jumlah' => $hcDitolakSaya,
                    'href' => route('healthcheck.index', ['status_approval' => 'Ditolak']),
                    'icon' => 'M18.36 6.64a9 9 0 11-12.73 0M12 2v10',
                ]);
            }
        }

        // ===== 1. KPI ringkas =====
        // Non-admin di-scope ke uker sendiri + SEMUA turunannya (bukan cuma
        // uker sendiri) -- mirip cara bangunTreeUker() ngumpulin akumulasi
        // buat admin, tapi root-nya uker_kode milik user, bukan Kanwil.
        $asetQuery = Aset::query();
        $formQuery = HealthCheckForm::query()->with('items');
        if (! $isAdmin) {
            $asetQuery->whereIn('uker_kode', $ukerBolehDiakses);
            $formQuery->whereIn('uker_kode', $ukerBolehDiakses);
        }

        $totalAset = $asetQuery->count();
        $formList = $formQuery->get();
        $totalFormHc = $formList->count();

        // Konteks tambahan di KPI card -- bukan "naik/turun %" beneran, karena
        // rataCompliance dihitung dari SELURUH histori item (bukan snapshot per
        // waktu tertentu), jadi gak ada cara jujur buat bilang "naik/turun X%
        // dari minggu lalu" tanpa nyimpen snapshot terpisah. Yang dikasih di
        // sini cuma angka yang emang bisa dihitung akurat dari created_at:
        // berapa yang BARU ditambahkan minggu ini.
        $asetBaruMingguIni = (clone $asetQuery)->where('created_at', '>=', now()->subDays(7))->count();
        $formBaruMingguIni = $formList->where('created_at', '>=', now()->subDays(7))->count();

        $totalItem = $formList->sum(fn ($f) => $f->items->count());
        $totalOk = $formList->sum(fn ($f) => $f->items->where('status', 'OK')->count());
        $rataCompliance = $totalItem > 0 ? round($totalOk / $totalItem * 100, 1) : 0;

        // ===== 1b. Kesehatan Checklist per Kategori (A-D + E) =====
        // Form TERBARU per uker (bukan seluruh histori) -- biar uker yang rajin
        // isi tiap minggu gak "menang banyak" dibanding uker yang baru isi
        // sekali. Reuse formTerbaruPerUkerScoped() yang sama dipakai itemDetail()
        // buat modal drill-down, biar angkanya konsisten satu sumber.
        $formTerbaruPerUker = $this->formTerbaruPerUkerScoped($request);

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

        // ===== 2b. Cabang Terbaik Bulan Ini (khusus admin) =====
        // Beda dari $rankingCabang di atas (yang nunjukin FORM per-form
        // paling rendah, buat dikejar) -- ini roll-up per CABANG (uker_spv,
        // sama pola kayak RekapController::rekapPerCabang()) dari compliance
        // BULAN INI aja, buat ngasih apresiasi/gamifikasi ke cabang yang lagi
        // paling rajin & rapi, bukan buat nyari yang bermasalah.
        $cabangTerbaikBulanIni = collect();
        if ($isAdmin) {
            $awalBulan = now()->copy()->startOfMonth();
            $akhirBulan = now()->copy()->endOfMonth();

            $cabangTerbaikBulanIni = HealthCheckForm::with(['uker', 'items'])
                ->whereBetween('tanggal_pemeriksaan', [$awalBulan->toDateString(), $akhirBulan->toDateString()])
                ->get()
                ->groupBy(fn ($form) => $form->uker?->uker_spv ?? 'Tidak diketahui')
                ->map(function ($formsDalamCabang, $namaCabang) {
                    $totalItem = $formsDalamCabang->sum(fn ($f) => $f->items->count());
                    $totalOk = $formsDalamCabang->sum(fn ($f) => $f->items->where('status', 'OK')->count());

                    return [
                        'cabang' => $namaCabang,
                        'persen' => $totalItem > 0 ? round($totalOk / $totalItem * 100, 1) : 0,
                        'jumlah_uker_lapor' => $formsDalamCabang->pluck('uker_kode')->unique()->count(),
                    ];
                })
                // Cabang tanpa item yang beneran keperiksa (0%) gak layak
                // masuk "terbaik", walau kebetulan ikut nge-generate form.
                ->filter(fn ($c) => $c['persen'] > 0)
                ->sortByDesc('persen')
                ->take(3)
                ->values();
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
            'aksiPerlu', 'totalAset', 'totalFormHc', 'rataCompliance', 'asetBaruMingguIni', 'formBaruMingguIni', 'kesehatanPerKategori', 'totalFormTerbaru', 'formLengkapDokumentasi',
            'rankingCabang', 'cabangTerbaikBulanIni', 'ukerBelumMengisi', 'ukerBelumAdaAset', 'editRequestsMenunggu', 'editRequestsSaya', 'distribusiPerangkat', 'aktivitasTerbaru', 'isAdmin', 'tree', 'totalKendalaAktif'
        ));
    }
}
