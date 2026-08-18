<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsUkerTree;
use App\Models\Aset;
use App\Models\HealthCheckForm;
use App\Models\Uker;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UkerTreeController extends Controller
{
    use BuildsUkerTree;

    // Root tree beda per role: admin dari Kanwil (146, lihat semua -- gak
    // berubah), user dari uker_kode dia sendiri (cuma subtree-nya). Cara
    // HITUNG tree-nya (bangunTreeUker) sama persis buat keduanya, cuma
    // titik awalnya yang beda.
    private function rootKodeUntuk(Request $request): int
    {
        return $request->user()->role === 'admin' ? 146 : $request->user()->uker_kode;
    }

    // User cuma boleh lihat detail node yang merupakan dirinya sendiri atau
    // turunannya (subtree-nya sendiri) -- kalau nyoba akses uker_kode di
    // luar itu (misal ketik manual di URL), ditolak 403, bukan cuma
    // disembunyikan di UI. Admin selalu boleh akses node manapun.
    private function authorizeAksesUker(Request $request, int $kode): void
    {
        if ($request->user()->role === 'admin') {
            return;
        }

        if (! in_array($kode, Uker::descendantKodes($request->user()->uker_kode))) {
            abort(403, 'Anda tidak punya akses ke uker ini.');
        }
    }

    // Halaman tree lengkap, berdiri sendiri (sebelumnya sempat digabung ke
    // dashboard, tapi di-revert karena bikin dashboard kepanjangan). Dashboard
    // sekarang cuma nampilin ringkasan kecil yang link ke sini.
    public function index(Request $request)
    {
        $tree = $this->bangunTreeUker($this->rootKodeUntuk($request));

        return view('uker-tree.index', compact('tree'));
    }

    // Dipanggil via AJAX pas tombol "Detail" diklik -- hitung rekap khusus
    // buat 1 node + semua anak-cucunya, cuma pas dibutuhkan (bukan pas load
    // halaman tree pertama kali, biar tetap ringan)
    public function detail(Request $request, int $kode)
    {
        $this->authorizeAksesUker($request, $kode);

        $semuaUker = Uker::all()->keyBy('kode');
        $node = $semuaUker->get($kode);
        if (! $node) {
            abort(404);
        }

        // Kumpulkan kode dirinya sendiri + semua keturunannya
        $kumpulanKode = Uker::descendantKodes($kode);

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
            'kode' => $node->kode,
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
        $this->authorizeAksesUker($request, $kode);

        $semuaUker = Uker::all()->keyBy('kode');
        $node = $semuaUker->get($kode);
        if (! $node) {
            abort(404);
        }

        $kumpulanKode = Uker::descendantKodes($kode);

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

    // ===================== EXPORT =====================
    // Tree-nya diratakan (flatten) jadi baris-baris tabel -- nama unit
    // diberi awalan "-- " x kedalaman biar struktur induk-anak tetap
    // kebaca meski dalam bentuk tabel datar. Root scoping SAMA kayak
    // index() (admin: dari Kanwil, user: subtree sendiri).

    protected function flattenTree(array $node, int $depth = 0): array
    {
        $baris = [[
            'kode' => $node['kode'],
            'nama' => str_repeat('-- ', $depth).$node['nama'],
            'jenis' => $node['jenis'],
            'total_aset' => $node['total_aset'],
            'rata_compliance' => $node['rata_compliance'] !== null ? $node['rata_compliance'].'%' : '-',
            'jumlah_unit_bawah' => $node['jumlah_unit_bawah'],
        ]];

        foreach ($node['anak'] as $anak) {
            $baris = array_merge($baris, $this->flattenTree($anak, $depth + 1));
        }

        return $baris;
    }

    protected function exportHeaders(): array
    {
        return ['Kode', 'Nama Unit', 'Jenis', 'Total Aset', 'Rata Compliance', 'Jumlah Unit di Bawahnya'];
    }

    protected function exportRow(array $r): array
    {
        return [$r['kode'], $r['nama'], $r['jenis'], $r['total_aset'], $r['rata_compliance'], $r['jumlah_unit_bawah']];
    }

    public function exportExcel(Request $request)
    {
        $tree = $this->bangunTreeUker($this->rootKodeUntuk($request));
        $baris = $tree ? $this->flattenTree($tree) : [];

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Struktur Organisasi');

        $headers = $this->exportHeaders();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        $row = 2;
        foreach ($baris as $r) {
            $sheet->fromArray($this->exportRow($r), null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'struktur-organisasi-'.now()->format('Ymd-His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $tree = $this->bangunTreeUker($this->rootKodeUntuk($request));
        $baris = $tree ? $this->flattenTree($tree) : [];

        $headers = $this->exportHeaders();
        $rows = collect($baris)->map(fn ($r) => $this->exportRow($r));
        $judul = 'Struktur Organisasi';

        $pdf = Pdf::loadView('rekap.pdf-generik', compact('headers', 'rows', 'judul'))->setPaper('a4', 'landscape');

        return $pdf->download('struktur-organisasi-'.now()->format('Ymd-His').'.pdf');
    }
}
