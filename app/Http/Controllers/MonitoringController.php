<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\Uker;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Halaman "Monitoring Kendala" -- kumpulan semua item checklist yang
// statusnya "Not OK" dari SELURUH uker/form, biar admin bisa mantau uker mana
// yang lagi ada masalah dan kenapa (bukan cuma lihat compliance % agregat).
class MonitoringController extends Controller
{
    // Non-admin cuma lihat kendala dari uker sendiri + seluruh turunannya
    // (subtree) -- reuse Uker::descendantKodes() yang sama dipakai di
    // Aset/Health Check/Struktur Organisasi, bukan scoping baru.
    protected function scopedQuery(Request $request)
    {
        $query = HealthCheckItem::with(['form.uker', 'statusLogs.changedBy'])->where('status', 'Not OK');

        if ($request->user()->role !== 'admin') {
            $ukerBolehDiakses = Uker::descendantKodes($request->user()->uker_kode);
            $query->whereHas('form', fn ($q) => $q->whereIn('uker_kode', $ukerBolehDiakses));
        }

        return $query;
    }

    protected function filteredQuery(Request $request)
    {
        $query = $this->scopedQuery($request);

        if ($request->filled('uker_kode')) {
            $query->whereHas('form', fn ($q) => $q->where('uker_kode', $request->input('uker_kode')));
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->input('kategori'));
        }

        if ($request->filled('status_tindak_lanjut')) {
            $query->where('status_tindak_lanjut', $request->input('status_tindak_lanjut'));
        }

        return $query;
    }

    // User cuma boleh update tindak lanjut item yang form-nya ada di
    // subtree-nya sendiri -- kalau nyoba lewat request langsung ke item di
    // luar itu (misal ketebak ID-nya), ditolak 403, bukan cuma disembunyikan
    // di UI (item itu emang gak pernah dirender ke non-admin di tabel).
    protected function authorizeAksesItem(Request $request, HealthCheckItem $item): void
    {
        if ($request->user()->role === 'admin') {
            return;
        }

        $ukerKode = $item->form?->uker_kode;
        if (! $ukerKode || ! in_array($ukerKode, Uker::descendantKodes($request->user()->uker_kode))) {
            abort(403, 'Anda tidak punya akses ke item ini.');
        }
    }

    // Ambang batas (hari) sebelum item yang belum ditindaklanjuti dianggap
    // "mendesak" -- dipakai buat badge di tabel & stat card.
    const AMBANG_HARI_MENDESAK = 3;

    // Ambang batas (hari) SLA khusus item yang statusnya SUDAH "Sedang
    // Diproses" -- dihitung dari mulai_diproses_at (kapan status itu pertama
    // kali tercatat), BUKAN dari tanggal_pemeriksaan seperti Mendesak.
    const AMBANG_HARI_SLA_DIPROSES = 7;

    // Urutkan yang belum ditindaklanjuti duluan (paling butuh perhatian), baru
    // sedang diproses, baru yang udah selesai; dalam tier yang sama, yang
    // paling lama didiamkan (tanggal pemeriksaan paling tua) duluan --
    // dilakukan di collection (bukan SQL) biar portable antara MySQL & SQLite
    // testing.
    protected function sortedItems(Request $request)
    {
        $prioritas = ['Belum Ditindaklanjuti' => 0, 'Sedang Diproses' => 1, 'Selesai Diperbaiki' => 2];

        return $this->filteredQuery($request)->get()
            ->sort(function ($a, $b) use ($prioritas) {
                $prioA = $prioritas[$a->status_tindak_lanjut] ?? 0;
                $prioB = $prioritas[$b->status_tindak_lanjut] ?? 0;

                if ($prioA !== $prioB) {
                    return $prioA <=> $prioB;
                }

                return ($a->form?->tanggal_pemeriksaan ?? now()) <=> ($b->form?->tanggal_pemeriksaan ?? now());
            })
            ->values();
    }

    // Public & static biar bisa dipakai juga di view (badge per baris tabel),
    // gak cuma di controller ini.
    public static function itemMendesak(HealthCheckItem $item): bool
    {
        if ($item->status_tindak_lanjut === 'Selesai Diperbaiki') {
            return false;
        }

        $tanggal = $item->form?->tanggal_pemeriksaan;

        return $tanggal && floor($tanggal->diffInDays(now())) > self::AMBANG_HARI_MENDESAK;
    }

    // Badge "Mendesak" MENGGANTI jadi SLA khusus begitu status_tindak_lanjut
    // = "Sedang Diproses" -- dihitung dari mulai_diproses_at, bukan
    // tanggal_pemeriksaan. Item yang belum pernah "Sedang Diproses" (kolom
    // masih null) belum kena SLA ini sama sekali.
    public static function itemMelewatiSlaDiproses(HealthCheckItem $item): bool
    {
        if ($item->status_tindak_lanjut !== 'Sedang Diproses' || ! $item->mulai_diproses_at) {
            return false;
        }

        return floor($item->mulai_diproses_at->diffInDays(now())) > self::AMBANG_HARI_SLA_DIPROSES;
    }

    // Jumlah hari YANG SUDAH LEWAT DARI BATAS (bukan total hari sejak mulai
    // diproses) -- dipakai buat keterangan badge, misal "Melewati batas 3
    // hari" buat item yang udah 10 hari diproses (10 - 7 = 3).
    public static function hariLewatSlaDiproses(HealthCheckItem $item): int
    {
        $hariBerjalan = (int) floor($item->mulai_diproses_at->diffInDays(now()));

        return max(0, $hariBerjalan - self::AMBANG_HARI_SLA_DIPROSES);
    }

    public function index(Request $request)
    {
        $items = $this->sortedItems($request);
        $isAdmin = $request->user()->role === 'admin';

        // Stat card dihitung dari scope user (bukan hasil filter aktif),
        // pola yang sama kayak Aset/Health Check index() -- biar stat di
        // atas stabil sementara tabel di bawahnya ikut filter.
        $itemsUntukStat = $this->scopedQuery($request)->with('form')->get();
        $totalBermasalah = $itemsUntukStat->count();
        $totalBelum = $itemsUntukStat->where('status_tindak_lanjut', 'Belum Ditindaklanjuti')->count();
        $totalSelesai = $itemsUntukStat->where('status_tindak_lanjut', 'Selesai Diperbaiki')->count();
        $totalMendesak = $itemsUntukStat
            ->where('status_tindak_lanjut', '!=', 'Selesai Diperbaiki')
            ->filter(fn ($item) => self::itemMendesak($item))
            ->count();

        $ukerFilterList = $isAdmin
            ? Uker::orderBy('nama')->get()
            : Uker::whereIn('kode', Uker::descendantKodes($request->user()->uker_kode))->orderBy('nama')->get();
        $kategoriList = array_keys(config('health_check_checklist'));

        return view('monitoring.index', compact(
            'items', 'totalBermasalah', 'totalBelum', 'totalSelesai', 'totalMendesak', 'ukerFilterList', 'kategoriList', 'isAdmin'
        ));
    }

    public function updateTindakLanjut(Request $request, HealthCheckItem $item)
    {
        $this->authorizeAksesItem($request, $item);

        $validated = $request->validate([
            'status_tindak_lanjut' => 'required|in:'.implode(',', HealthCheckForm::DAFTAR_STATUS_TINDAK_LANJUT),
            'catatan_tindak_lanjut' => 'nullable|string',
        ]);

        // Catat mulai_diproses_at OTOMATIS & SEKALI SAJA -- syaratnya harus
        // dicek SEBELUM update() jalan (biar gak kejebak nilai baru yang
        // baru aja di-set), dan kolomnya masih null (kalau statusnya
        // bolak-balik ke "Sedang Diproses" lagi, nilai pertama TETAP dipakai).
        $sedangDiprosesPertamaKali = $validated['status_tindak_lanjut'] === 'Sedang Diproses' && ! $item->mulai_diproses_at;

        $item->update($validated);

        if ($sedangDiprosesPertamaKali) {
            $item->update(['mulai_diproses_at' => now()]);
        }

        // Riwayat perubahan -- SELALU insert baris baru (bukan overwrite),
        // independen dari mulai_diproses_at/SLA di atas, dipakai buat modal
        // "Lihat Riwayat".
        $item->statusLogs()->create([
            'status' => $validated['status_tindak_lanjut'],
            'catatan' => $validated['catatan_tindak_lanjut'] ?? null,
            'changed_by' => $request->user()->id,
        ]);

        ActivityLog::catat(
            'health_check',
            'update_tindak_lanjut_item',
            1,
            "Tindak lanjut item '{$item->item_pemeriksaan}' ({$item->form?->uker?->nama}) diupdate jadi {$validated['status_tindak_lanjut']}"
        );

        return back()->with('status', 'Status tindak lanjut item berhasil diupdate.');
    }

    // ===================== EXPORT =====================

    protected function exportHeaders(): array
    {
        return ['Uker', 'Kategori', 'Item Bermasalah', 'Catatan', 'Periode', 'Tanggal Pemeriksaan', 'Status Tindak Lanjut', 'Catatan Tindak Lanjut'];
    }

    protected function exportRow(HealthCheckItem $item): array
    {
        return [
            $item->form?->uker?->nama,
            $item->kategori,
            $item->item_pemeriksaan,
            $item->catatan,
            $item->form?->periode,
            $item->form?->tanggal_pemeriksaan,
            $item->status_tindak_lanjut,
            $item->catatan_tindak_lanjut,
        ];
    }

    public function exportExcel(Request $request)
    {
        $items = $this->sortedItems($request);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Monitoring Kendala');

        $headers = $this->exportHeaders();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        $row = 2;
        foreach ($items as $item) {
            $sheet->fromArray($this->exportRow($item), null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'monitoring-kendala-'.now()->format('Ymd-His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $items = $this->sortedItems($request);
        $headers = $this->exportHeaders();

        $pdf = Pdf::loadView('monitoring.pdf', compact('items', 'headers'))->setPaper('a4', 'landscape');

        return $pdf->download('monitoring-kendala-'.now()->format('Ymd-His').'.pdf');
    }
}
