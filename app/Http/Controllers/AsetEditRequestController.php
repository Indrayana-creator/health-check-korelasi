<?php

namespace App\Http\Controllers;

use App\Models\AsetEditRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AsetEditRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = AsetEditRequest::with(['aset.uker', 'requester'])
            ->orderByRaw("status = 'Menunggu' desc")
            ->orderByDesc('created_at')
            ->paginate(20);

        $totalMenunggu = AsetEditRequest::where('status', 'Menunggu')->count();
        $totalDisetujui = AsetEditRequest::where('status', 'Disetujui')->count();
        $totalKeseluruhan = AsetEditRequest::count();

        return view('edit-requests.index', compact('requests', 'totalMenunggu', 'totalDisetujui', 'totalKeseluruhan'));
    }

    // ===================== EXPORT =====================
    // Sengaja EXPORT SEMUA (gak paginate) -- beda dari index() yang
    // paginate(20) buat tampilan layar.

    protected function exportHeaders(): array
    {
        return ['Aset', 'Uker', 'Diajukan Oleh', 'Alasan', 'Status', 'Catatan Admin', 'Ditangani Oleh', 'Tanggal Diajukan'];
    }

    protected function exportRow(AsetEditRequest $r): array
    {
        return [
            $r->aset?->no_asset, $r->aset?->uker?->nama, $r->requester?->name, $r->alasan,
            $r->status, $r->catatan_admin, $r->handler?->name, $r->created_at?->format('Y-m-d H:i'),
        ];
    }

    public function exportExcel()
    {
        $requests = AsetEditRequest::with(['aset.uker', 'requester', 'handler'])->latest()->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Permintaan Edit Aset');

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

        $filename = 'permintaan-edit-aset-'.now()->format('Ymd-His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf()
    {
        $requests = AsetEditRequest::with(['aset.uker', 'requester', 'handler'])->latest()->get();
        $headers = $this->exportHeaders();
        $rows = $requests->map(fn ($r) => $this->exportRow($r));
        $judul = 'Permintaan Edit Aset';

        $pdf = Pdf::loadView('rekap.pdf-generik', compact('headers', 'rows', 'judul'))->setPaper('a4', 'landscape');

        return $pdf->download('permintaan-edit-aset-'.now()->format('Ymd-His').'.pdf');
    }
}
