<?php

namespace App\Http\Controllers;

use App\Models\Aset;
use App\Models\AsetKondisiLog;
use App\Models\HealthCheckForm;
use App\Models\PermintaanPerangkat;
use App\Support\ComplianceScale;
use App\Support\PeriodeMingguan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        ['minggu' => $rekapMingguan, 'bulan' => $rekapBulanan, 'labelMinggu' => $labelMinggu, 'labelBulan' => $labelBulan, 'formList' => $formList]
            = $this->rekapCabangMingguDanBulan();

        $statMingguan = $this->ringkasStat($rekapMingguan);
        $statBulanan = $this->ringkasStat($rekapBulanan);

        $trenCompliance = $this->hitungTrenCompliance($formList);

        return view('rekap.index', compact(
            'rekapMingguan', 'rekapBulanan', 'statMingguan', 'statBulanan',
            'trenCompliance', 'labelMinggu', 'labelBulan'
        ));
    }

    // Dipakai bareng oleh index() (butuh dua-duanya buat toggle) DAN export
    // (butuh salah satu tergantung ?periode=minggu|bulan) -- biar gak
    // duplikat logic hitung rentang tanggal & roll-up per cabang.
    protected function rekapCabangMingguDanBulan(): array
    {
        $formList = HealthCheckForm::with(['uker', 'items'])->get();

        [$awalMinggu, $akhirMinggu] = PeriodeMingguan::rentang(now());
        $awalBulan = now()->copy()->startOfMonth();
        $akhirBulan = now()->copy()->endOfMonth();

        $formMingguIni = $formList->filter(fn ($f) => $f->tanggal_pemeriksaan?->between($awalMinggu, $akhirMinggu));
        $formBulanIni = $formList->filter(fn ($f) => $f->tanggal_pemeriksaan?->between($awalBulan, $akhirBulan));

        return [
            'minggu' => $this->rekapPerCabang($formMingguIni),
            'bulan' => $this->rekapPerCabang($formBulanIni),
            'labelMinggu' => PeriodeMingguan::label(now()),
            'labelBulan' => now()->locale('id')->translatedFormat('F Y'),
            'formList' => $formList,
        ];
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

    // ===================== EXPORT: Rekap Health Check per Cabang =====================
    // ?periode=minggu (default) atau ?periode=bulan -- reuse computation yang
    // sama dipakai index(), jadi hasil export SELALU konsisten sama tab yang
    // lagi aktif di layar (link export di view kirim query ini sesuai toggle).

    protected function rekapCabangHeaders(): array
    {
        return ['Cabang', 'Uker Lapor', 'Total Item', 'OK', 'Not OK', 'N/A', 'Belum', 'Compliance (%)', 'Status'];
    }

    protected function rekapCabangRow(array $r): array
    {
        return [
            $r['cabang'], $r['jumlah_uker_lapor'], $r['total_item'],
            $r['ok'], $r['not_ok'], $r['na'], $r['belum'], $r['persen'], $r['status'],
        ];
    }

    protected function rekapCabangUntukExport(Request $request): array
    {
        $data = $this->rekapCabangMingguDanBulan();
        $periode = $request->input('periode') === 'bulan' ? 'bulan' : 'minggu';

        return $periode === 'bulan'
            ? [$data['bulan'], $data['labelBulan']]
            : [$data['minggu'], $data['labelMinggu']];
    }

    public function exportCabangExcel(Request $request)
    {
        [$rekap, $label] = $this->rekapCabangUntukExport($request);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Health Check');

        $sheet->setCellValue('A1', "Rekap Health Check per Cabang - {$label}");
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true);

        $headers = $this->rekapCabangHeaders();
        $sheet->fromArray($headers, null, 'A3');
        $sheet->getStyle('A3:I3')->getFont()->setBold(true);

        $row = 4;
        foreach ($rekap as $r) {
            $sheet->fromArray($this->rekapCabangRow($r), null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'rekap-health-check-'.now()->format('Ymd-His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportCabangPdf(Request $request)
    {
        [$rekap, $label] = $this->rekapCabangUntukExport($request);
        $headers = $this->rekapCabangHeaders();
        $rows = $rekap->map(fn ($r) => $this->rekapCabangRow($r));
        $judul = "Rekap Health Check per Cabang - {$label}";

        $pdf = Pdf::loadView('rekap.pdf-generik', compact('headers', 'rows', 'judul'))->setPaper('a4', 'landscape');

        return $pdf->download('rekap-health-check-'.now()->format('Ymd-His').'.pdf');
    }

    // ===================== EXPORT: Rekap Aset per Cabang =====================

    protected function rekapAsetHeaders(): array
    {
        return ['Cabang', 'Uker Lapor', 'Total', 'Normal', 'Rusak', 'Tidak Layak', 'Lainnya', 'Sehat (%)', 'Data Lengkap (%)', 'Status'];
    }

    protected function rekapAsetRow(array $r): array
    {
        return [
            $r['cabang'], $r['jumlah_uker_lapor'], $r['total'],
            $r['normal'], $r['rusak'], $r['tidak_layak'], $r['lainnya'], $r['persen_sehat'], $r['persen_lengkap'], $r['status'],
        ];
    }

    public function exportAsetExcel()
    {
        $rekap = $this->rekapAsetPerCabang();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Aset');

        $headers = $this->rekapAsetHeaders();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);

        $row = 2;
        foreach ($rekap as $r) {
            $sheet->fromArray($this->rekapAsetRow($r), null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'rekap-aset-'.now()->format('Ymd-His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportAsetPdf()
    {
        $rekap = $this->rekapAsetPerCabang();
        $headers = $this->rekapAsetHeaders();
        $rows = $rekap->map(fn ($r) => $this->rekapAsetRow($r));
        $judul = 'Rekap Aset per Cabang';

        $pdf = Pdf::loadView('rekap.pdf-generik', compact('headers', 'rows', 'judul'))->setPaper('a4', 'landscape');

        return $pdf->download('rekap-aset-'.now()->format('Ymd-His').'.pdf');
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
    // Diekstrak jadi method sendiri biar dipakai bareng aset() & export.
    protected function rekapAsetPerCabang()
    {
        return Aset::with(['uker', 'kodeAset'])->get()
            ->groupBy(fn ($aset) => $aset->uker?->uker_spv ?? 'Tidak diketahui')
            ->map(function ($asetDalamCabang, $namaCabang) {
                $total = $asetDalamCabang->count();
                $normal = $asetDalamCabang->where('kondisi', 'NORMAL')->count();
                $rusak = $asetDalamCabang->where('kondisi', 'RUSAK')->count();
                $tidakLayak = $asetDalamCabang->where('kondisi', 'TIDAK LAYAK')->count();
                $lainnya = $total - $normal - $rusak - $tidakLayak;
                $persenSehat = $total > 0 ? round($normal / $total * 100, 1) : 0;
                $totalLengkap = $asetDalamCabang->filter(fn ($a) => $this->asetDataLengkap($a))->count();
                $persenLengkap = $total > 0 ? round($totalLengkap / $total * 100, 1) : 0;

                return [
                    'cabang' => $namaCabang,
                    'jumlah_uker_lapor' => $asetDalamCabang->pluck('uker_kode')->unique()->count(),
                    'total' => $total,
                    'normal' => $normal,
                    'rusak' => $rusak,
                    'tidak_layak' => $tidakLayak,
                    'lainnya' => $lainnya,
                    'persen_sehat' => $persenSehat,
                    'persen_lengkap' => $persenLengkap,
                    'status' => $persenSehat >= 95 ? 'SANGAT BAIK' : ($persenSehat >= 80 ? 'BAIK' : 'PERLU PERHATIAN'),
                ];
            })
            ->sortBy('persen_sehat')
            ->values();
    }

    // Aset dianggap "lengkap" kalau merek & SN keisi, DAN kalau kategorinya
    // termasuk yang wajib punya pemegang individu (PC/Notebook/Tablet/Monitor
    // -- lihat Aset::KATEGORI_PEMEGANG_INDIVIDU), 8 field pemegang & keamanan
    // ikut keisi semua. Dipakai buat indikator "% Data Lengkap" per cabang,
    // BEDA dari "% Sehat" yang ngukur kondisi fisik -- ini ngukur kelengkapan
    // catatan datanya, dua hal independen (aset bisa NORMAL tapi datanya
    // bolong, atau sebaliknya).
    protected function asetDataLengkap(Aset $aset): bool
    {
        if (! $aset->merek || ! $aset->sn) {
            return false;
        }

        if (! in_array($aset->kodeAset?->kategori, Aset::KATEGORI_PEMEGANG_INDIVIDU)) {
            return true;
        }

        foreach (['pemegang_nama', 'jabatan', 'pemegang_pn', 'ip_address', 'status_hardening', 'status_bitlocker', 'status_dlp', 'status_antivirus'] as $field) {
            if (! $aset->{$field}) {
                return false;
            }
        }

        return true;
    }

    // Tren perubahan kondisi aset per bulan -- "baru_rusak" (transisi KE
    // RUSAK/TIDAK LAYAK) vs "diperbaiki" (transisi KE NORMAL), diambil dari
    // AsetKondisiLog (riwayat perubahan kondisi). BEDA dari distribusi kondisi
    // di atas yang cuma snapshot SAAT INI -- ini ngukur ARAH pergerakannya
    // dari waktu ke waktu, biar keliatan makin membaik atau memburuk.
    protected function trenKondisiAset(): array
    {
        return AsetKondisiLog::orderBy('created_at')->get()
            ->groupBy(fn ($log) => $log->created_at->format('Y-m'))
            ->map(function ($logsBulan, $bulan) {
                return [
                    'bulan' => $bulan,
                    'label' => Carbon::createFromFormat('Y-m', $bulan)->locale('id')->translatedFormat('M Y'),
                    'baru_rusak' => $logsBulan->whereIn('kondisi_baru', ['RUSAK', 'TIDAK LAYAK'])->count(),
                    'diperbaiki' => $logsBulan->where('kondisi_baru', 'NORMAL')->count(),
                ];
            })
            ->sortBy('bulan')
            ->values()
            ->all();
    }

    public function aset(Request $request)
    {
        $asetList = Aset::with('uker')->get();
        $rekap = $this->rekapAsetPerCabang();
        $trenKondisi = $this->trenKondisiAset();

        $totalCabang = $rekap->count();
        $avgPersenSehat = $totalCabang > 0 ? round($rekap->avg('persen_sehat'), 1) : 0;
        $avgPersenLengkap = $totalCabang > 0 ? round($rekap->avg('persen_lengkap'), 1) : 0;
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

        return view('rekap.aset', compact('rekap', 'totalCabang', 'avgPersenSehat', 'avgPersenLengkap', 'totalPerluPerhatian', 'distribusiKondisi', 'trenKondisi'));
    }

    // Rekap Permintaan Perangkat per MINGGU (Senin-Jumat), bisa navigasi
    // maju/mundur antar minggu lewat query string ?minggu=YYYY-MM-DD (tanggal
    // apa aja dalam minggu yang dituju) -- pola rentang minggunya reuse
    // PeriodeMingguan yang sama dipakai di siklus Health Check mingguan.
    public function permintaanPerangkat(Request $request)
    {
        $request->validate(['minggu' => 'nullable|date']);

        [$permintaanList, $labelMinggu, $awalMinggu] = $this->permintaanMingguan($request);

        $totalMinggu = $permintaanList->count();
        $breakdownStatus = collect(PermintaanPerangkat::DAFTAR_STATUS)
            ->mapWithKeys(fn ($status) => [$status => $permintaanList->where('status', $status)->count()]);

        $mingguSebelumnya = $awalMinggu->copy()->subWeek()->toDateString();
        $mingguSesudahnya = $awalMinggu->copy()->addWeek()->toDateString();

        return view('rekap.permintaan-perangkat', compact(
            'permintaanList', 'totalMinggu', 'breakdownStatus', 'labelMinggu', 'mingguSebelumnya', 'mingguSesudahnya'
        ));
    }

    // Dipakai bareng permintaanPerangkat() & export -- biar minggu yang
    // di-export SELALU sama persis minggu yang lagi ditampilkan/dinavigasi
    // (link export ikut nerusin query ?minggu= yang sama).
    protected function permintaanMingguan(Request $request): array
    {
        $tanggalAcuan = $request->filled('minggu')
            ? Carbon::parse($request->input('minggu'))
            : now();

        [$awalMinggu, $akhirMinggu] = PeriodeMingguan::rentang($tanggalAcuan);

        $permintaanList = PermintaanPerangkat::with('uker')
            ->whereBetween('tanggal_request', [$awalMinggu->toDateString(), $akhirMinggu->toDateString()])
            ->orderBy('tanggal_request')
            ->get();

        return [$permintaanList, PeriodeMingguan::label($tanggalAcuan), $awalMinggu];
    }

    // ===================== EXPORT: Rekap Permintaan Perangkat =====================

    protected function rekapPermintaanHeaders(): array
    {
        return ['No Nota Dinas', 'Tanggal Request', 'Fungsi Requester', 'Jumlah', 'Status', 'Keterangan', 'Uker'];
    }

    protected function rekapPermintaanRow(PermintaanPerangkat $p): array
    {
        return [
            $p->no_nota_dinas, $p->tanggal_request?->format('Y-m-d'), $p->fungsi_requester,
            $p->jumlah, $p->status, $p->keterangan, $p->uker?->nama,
        ];
    }

    public function exportPermintaanExcel(Request $request)
    {
        $request->validate(['minggu' => 'nullable|date']);
        [$permintaanList, $label] = $this->permintaanMingguan($request);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Permintaan Perangkat');

        $sheet->setCellValue('A1', "Rekap Permintaan Perangkat - {$label}");
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true);

        $headers = $this->rekapPermintaanHeaders();
        $sheet->fromArray($headers, null, 'A3');
        $sheet->getStyle('A3:G3')->getFont()->setBold(true);

        $row = 4;
        foreach ($permintaanList as $p) {
            $sheet->fromArray($this->rekapPermintaanRow($p), null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'rekap-permintaan-perangkat-'.now()->format('Ymd-His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPermintaanPdf(Request $request)
    {
        $request->validate(['minggu' => 'nullable|date']);
        [$permintaanList, $label] = $this->permintaanMingguan($request);

        $headers = $this->rekapPermintaanHeaders();
        $rows = $permintaanList->map(fn ($p) => $this->rekapPermintaanRow($p));
        $judul = "Rekap Permintaan Perangkat - {$label}";

        $pdf = Pdf::loadView('rekap.pdf-generik', compact('headers', 'rows', 'judul'))->setPaper('a4', 'landscape');

        return $pdf->download('rekap-permintaan-perangkat-'.now()->format('Ymd-His').'.pdf');
    }
}
