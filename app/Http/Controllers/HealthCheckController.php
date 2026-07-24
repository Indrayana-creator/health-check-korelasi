<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\Uker;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class HealthCheckController extends Controller
{
    protected function scopedQuery(Request $request)
    {
        $query = HealthCheckForm::with(['uker', 'items']);
        if ($request->user()->role !== 'admin') {
            $query->where('uker_kode', $request->user()->uker_kode);
        }
        return $query->orderBy('uker_kode');
    }

    protected function filteredQuery(Request $request)
    {
        $query = $this->scopedQuery($request);

        if ($request->filled('q')) {
            $query->where('periode', 'like', '%' . $request->input('q') . '%');
        }

        if ($request->filled('uker_kode')) {
            $query->where('uker_kode', $request->input('uker_kode'));
        }

        return $query;
    }

    public function index(Request $request)
    {
        $formList = $this->filteredQuery($request)->withCount('items')->orderByDesc('id')->paginate(20)->withQueryString();
        $ukerFilterList = $request->user()->role === 'admin' ? Uker::orderBy('nama')->get() : collect();

        return view('healthcheck.index', compact('formList', 'ukerFilterList'));
    }

    public function create(Request $request)
    {
        $ukerList = $request->user()->role === 'admin'
            ? Uker::orderBy('nama')->get()
            : Uker::where('kode', $request->user()->uker_kode)->get();

        return view('healthcheck.create', compact('ukerList'));
    }

    protected function generateItemsUntukForm(HealthCheckForm $form): void
    {
        $checklist = config('health_check_checklist');
        foreach ($checklist as $kategori => $items) {
            foreach ($items as $itemText) {
                HealthCheckItem::create([
                    'health_check_form_id' => $form->id,
                    'kategori' => $kategori,
                    'item_pemeriksaan' => $itemText,
                    'status' => 'Belum Diperiksa',
                ]);
            }
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'uker_kode' => 'required|integer|exists:ukers,kode',
            'tanggal_pemeriksaan' => 'required|date',
            'periode' => 'required|string|max:50',
            'pic_pn' => 'nullable|string|max:50|exists:pekerja,pn',
        ]);

        if ($request->user()->role !== 'admin' && $validated['uker_kode'] != $request->user()->uker_kode) {
            abort(403, 'Anda hanya bisa membuat form health check untuk uker Anda sendiri.');
        }

        $form = HealthCheckForm::create($validated);
        $this->generateItemsUntukForm($form);

        return redirect()->route('healthcheck.edit', $form)->with('status', 'Form health check dibuat. Silakan isi status tiap item pemeriksaan.');
    }

    public function edit(Request $request, HealthCheckForm $healthcheck)
    {
        if ($request->user()->role !== 'admin' && $healthcheck->uker_kode != $request->user()->uker_kode) {
            abort(403, 'Anda tidak punya akses ke form ini.');
        }

        $itemsByKategori = $healthcheck->items()->orderBy('id')->get()->groupBy('kategori');

        return view('healthcheck.edit', compact('healthcheck', 'itemsByKategori'));
    }

    public function update(Request $request, HealthCheckForm $healthcheck)
    {
        if ($request->user()->role !== 'admin' && $healthcheck->uker_kode != $request->user()->uker_kode) {
            abort(403, 'Anda tidak punya akses ke form ini.');
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:health_check_items,id',
            'items.*.status' => 'required|in:OK,Not OK,N/A,Belum Diperiksa',
            'items.*.catatan' => 'nullable|string',
        ]);

        foreach ($validated['items'] as $itemInput) {
            HealthCheckItem::where('id', $itemInput['id'])
                ->where('health_check_form_id', $healthcheck->id)
                ->update([
                    'status' => $itemInput['status'],
                    'catatan' => $itemInput['catatan'] ?? null,
                ]);
        }

        return redirect()->route('healthcheck.index')->with('status', 'Hasil pemeriksaan berhasil disimpan.');
    }

    public function destroy(Request $request, HealthCheckForm $healthcheck)
    {
        if ($request->user()->role !== 'admin' && $healthcheck->uker_kode != $request->user()->uker_kode) {
            abort(403, 'Anda tidak punya akses ke form ini.');
        }

        $healthcheck->delete();

        return redirect()->route('healthcheck.index')->with('status', 'Form health check dihapus.');
    }

    // ===================== BULK UPLOAD (Excel) =====================
    // Tiap baris = 1 form baru (uker + tanggal + periode), 61 item checklist
    // digenerate otomatis persis seperti store() manual.

    public function bulkUploadForm(Request $request)
    {
        return view('healthcheck.bulk-upload');
    }

    public function bulkUpload(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        $namaFile = $request->file('file')->getClientOriginalName();
        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $isAdmin = $request->user()->role === 'admin';
        $ukerSendiri = $request->user()->uker_kode;

        $berhasil = 0;
        $gagal = [];

        // Kolom: A=uker_kode, B=tanggal_pemeriksaan (YYYY-MM-DD), C=periode, D=pic_pn (opsional)
        for ($row = 2; $row <= $highestRow; $row++) {
            $ukerKode = $sheet->getCell("A{$row}")->getValue();
            $tanggal = $sheet->getCell("B{$row}")->getValue();
            $periode = trim((string) $sheet->getCell("C{$row}")->getValue());

            if (!$ukerKode || !$tanggal || !$periode) {
                continue;
            }

            if (!$isAdmin && (int) $ukerKode !== (int) $ukerSendiri) {
                $gagal[] = "Baris {$row}: uker_kode {$ukerKode} bukan milik Anda";
                continue;
            }

            if (!Uker::where('kode', $ukerKode)->exists()) {
                $gagal[] = "Baris {$row}: kode uker {$ukerKode} tidak ditemukan";
                continue;
            }

            try {
                $form = HealthCheckForm::create([
                    'uker_kode' => (int) $ukerKode,
                    'tanggal_pemeriksaan' => $tanggal,
                    'periode' => $periode,
                    'pic_pn' => (string) $sheet->getCell("D{$row}")->getValue() ?: null,
                ]);
                $this->generateItemsUntukForm($form);
                $berhasil++;
            } catch (\Throwable $e) {
                $gagal[] = "Baris {$row}: " . $e->getMessage();
            }
        }

        if ($berhasil > 0) {
            ActivityLog::catat('health_check', 'upload_massal', $berhasil, "Upload massal dari file: {$namaFile}");
        }

        return back()->with('status', "Upload massal selesai: {$berhasil} form health check dibuat (masing-masing otomatis berisi 61 item checklist).")
            ->with('gagal', $gagal);
    }

    // ===================== BULK DELETE (Excel) =====================

    public function bulkDeleteForm(Request $request)
    {
        return view('healthcheck.bulk-delete');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        $namaFile = $request->file('file')->getClientOriginalName();
        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $isAdmin = $request->user()->role === 'admin';
        $ukerSendiri = $request->user()->uker_kode;

        $terhapus = 0;
        $tidakKetemu = [];

        // Kolom: A=uker_kode, B=periode -- pasangan ini yang jadi kunci pencarian form
        for ($row = 2; $row <= $highestRow; $row++) {
            $ukerKode = $sheet->getCell("A{$row}")->getValue();
            $periode = trim((string) $sheet->getCell("B{$row}")->getValue());

            if (!$ukerKode || !$periode) {
                continue;
            }

            $query = HealthCheckForm::where('uker_kode', $ukerKode)->where('periode', $periode);
            if (!$isAdmin) {
                $query->where('uker_kode', $ukerSendiri);
            }

            $form = $query->first();
            if (!$form) {
                $tidakKetemu[] = "Baris {$row}: uker {$ukerKode} periode {$periode} tidak ditemukan (atau bukan milik Anda)";
                continue;
            }

            $form->delete(); // items ikut terhapus otomatis (cascadeOnDelete di migration)
            $terhapus++;
        }

        if ($terhapus > 0) {
            ActivityLog::catat('health_check', 'delete_massal', $terhapus, "Delete massal dari file: {$namaFile}");
        }

        return back()->with('status', "Delete massal selesai: {$terhapus} form health check berhasil dihapus.")
            ->with('gagal', $tidakKetemu);
    }

    // ===================== RINGKASAN & EXPORT =====================

    protected function ringkasanKategori(HealthCheckForm $form): array
    {
        $hasil = [];
        foreach ($form->items->groupBy('kategori') as $kategori => $items) {
            $total = $items->count();
            $ok = $items->where('status', 'OK')->count();
            $hasil[] = [
                'kategori' => $kategori,
                'total' => $total,
                'ok' => $ok,
                'not_ok' => $items->where('status', 'Not OK')->count(),
                'na' => $items->where('status', 'N/A')->count(),
                'belum' => $items->where('status', 'Belum Diperiksa')->count(),
                'persen' => $total ? round($ok / $total * 100, 1) : 0,
            ];
        }
        return $hasil;
    }

    public function exportExcel(Request $request)
    {
        $formList = $this->filteredQuery($request)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Health Check');

        $headers = ['Uker', 'Periode', 'Tanggal', 'Kategori', 'Total Item', 'OK', 'Not OK', 'N/A', 'Belum Diperiksa', 'Compliance (%)'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);

        $row = 2;
        foreach ($formList as $form) {
            foreach ($this->ringkasanKategori($form) as $r) {
                $sheet->fromArray([
                    $form->uker?->nama,
                    $form->periode,
                    $form->tanggal_pemeriksaan,
                    $r['kategori'],
                    $r['total'],
                    $r['ok'],
                    $r['not_ok'],
                    $r['na'],
                    $r['belum'],
                    $r['persen'],
                ], null, "A{$row}");
                $row++;
            }
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'health-check-' . now()->format('Ymd-His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $formList = $this->filteredQuery($request)->get();
        $ringkasanPerForm = [];
        foreach ($formList as $form) {
            $ringkasanPerForm[] = [
                'form' => $form,
                'kategori' => $this->ringkasanKategori($form),
            ];
        }

        $pdf = Pdf::loadView('healthcheck.pdf', compact('ringkasanPerForm'))->setPaper('a4', 'landscape');

        return $pdf->download('health-check-' . now()->format('Ymd-His') . '.pdf');
    }
}
