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
    protected function filteredQuery(Request $request)
    {
        $query = HealthCheckItem::with(['form.uker'])->where('status', 'Not OK');

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

    // Ambang batas (hari) sebelum item yang belum ditindaklanjuti dianggap
    // "mendesak" -- dipakai buat badge di tabel & stat card.
    const AMBANG_HARI_MENDESAK = 3;

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

    public function index(Request $request)
    {
        $items = $this->sortedItems($request);

        $totalBermasalah = HealthCheckItem::where('status', 'Not OK')->count();
        $totalBelum = HealthCheckItem::where('status', 'Not OK')->where('status_tindak_lanjut', 'Belum Ditindaklanjuti')->count();
        $totalSelesai = HealthCheckItem::where('status', 'Not OK')->where('status_tindak_lanjut', 'Selesai Diperbaiki')->count();
        $totalMendesak = HealthCheckItem::where('status', 'Not OK')
            ->where('status_tindak_lanjut', '!=', 'Selesai Diperbaiki')
            ->with('form')
            ->get()
            ->filter(fn ($item) => self::itemMendesak($item))
            ->count();

        $ukerFilterList = Uker::orderBy('nama')->get();
        $kategoriList = array_keys(config('health_check_checklist'));

        return view('monitoring.index', compact(
            'items', 'totalBermasalah', 'totalBelum', 'totalSelesai', 'totalMendesak', 'ukerFilterList', 'kategoriList'
        ));
    }

    public function updateTindakLanjut(Request $request, HealthCheckItem $item)
    {
        $validated = $request->validate([
            'status_tindak_lanjut' => 'required|in:'.implode(',', HealthCheckForm::DAFTAR_STATUS_TINDAK_LANJUT),
            'catatan_tindak_lanjut' => 'nullable|string',
        ]);

        $item->update($validated);
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
