<?php

namespace App\Http\Controllers;

use App\Models\AsetHapusRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AsetHapusRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = AsetHapusRequest::with(['aset.uker', 'requester'])
            ->orderByRaw("status = 'Menunggu' desc")
            ->orderByDesc('created_at')
            ->paginate(20);

        $totalMenunggu = AsetHapusRequest::where('status', 'Menunggu')->count();
        $totalDisetujui = AsetHapusRequest::where('status', 'Disetujui')->count();
        $totalKeseluruhan = AsetHapusRequest::count();

        return view('hapus-requests.index', compact('requests', 'totalMenunggu', 'totalDisetujui', 'totalKeseluruhan'));
    }

    // ===================== EXPORT =====================
    // Sengaja EXPORT SEMUA (gak paginate) -- beda dari index() yang
    // paginate(20) buat tampilan layar.

    protected function exportHeaders(): array
    {
        return ['Aset', 'Uker', 'Diajukan Oleh', 'Alasan', 'Status', 'Catatan Admin', 'Ditangani Oleh', 'Tanggal Diajukan'];
    }

    protected function exportRow(AsetHapusRequest $r): array
    {
        return [
            $r->aset?->no_asset, $r->aset?->uker?->nama, $r->requester?->name, $r->alasan,
            $r->status, $r->catatan_admin, $r->handler?->name, $r->created_at?->format('Y-m-d H:i'),
        ];
    }

    public function exportExcel()
    {
        $requests = AsetHapusRequest::with(['aset.uker', 'requester', 'handler'])->latest()->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Permintaan Hapus Aset');

        $headers = $this->exportHeaders();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        $row = 2;
        foreach ($requests as $r) {
            $sheet->fromArray($this->exportRow($r), null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'permintaan-hapus-aset-'.now()->format('Ymd-His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf()
    {
        $requests = AsetHapusRequest::with(['aset.uker', 'requester', 'handler'])->latest()->get();
        $headers = $this->exportHeaders();
        $rows = $requests->map(fn ($r) => $this->exportRow($r));
        $judul = 'Permintaan Hapus Aset';

        $pdf = Pdf::loadView('rekap.pdf-generik', compact('headers', 'rows', 'judul'))->setPaper('a4', 'landscape');

        return $pdf->download('permintaan-hapus-aset-'.now()->format('Ymd-His').'.pdf');
    }
}
