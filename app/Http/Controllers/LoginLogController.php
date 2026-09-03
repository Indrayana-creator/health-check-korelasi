<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LoginLogController extends Controller
{
    protected function filteredQuery(Request $request)
    {
        $query = LoginLog::with('user');

        if ($request->filled('tahun')) {
            $query->whereYear('created_at', $request->input('tahun'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('pn_dicoba', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
            });
        }

        return $query->orderByDesc('created_at');
    }

    public function index(Request $request)
    {
        $logs = $this->filteredQuery($request)->paginate(20)->withQueryString();

        $tahunRingkasan = $request->input('tahun', now()->year);
        $totalBerhasil = LoginLog::whereYear('created_at', $tahunRingkasan)->where('status', LoginLog::STATUS_BERHASIL)->count();
        $totalGagal = LoginLog::whereYear('created_at', $tahunRingkasan)->where('status', '!=', LoginLog::STATUS_BERHASIL)->count();

        $tahunTersedia = LoginLog::pluck('created_at')
            ->map(fn ($tanggal) => $tanggal->year)
            ->unique()
            ->sortDesc()
            ->values();
        if ($tahunTersedia->isEmpty()) {
            $tahunTersedia = collect([now()->year]);
        }

        return view('login-history.index', compact('logs', 'tahunRingkasan', 'totalBerhasil', 'totalGagal', 'tahunTersedia'));
    }

    // ===================== EXPORT =====================

    protected function exportHeaders(): array
    {
        return ['Waktu', 'PN Dicoba', 'Nama User', 'Status', 'IP Address', 'User Agent'];
    }

    protected function exportRow(LoginLog $log): array
    {
        return [
            $log->created_at->format('d-m-Y H:i:s'),
            $log->pn_dicoba,
            $log->user?->name,
            LoginLog::LABEL_STATUS[$log->status] ?? $log->status,
            $log->ip_address,
            $log->user_agent,
        ];
    }

    public function exportExcel(Request $request)
    {
        $logs = $this->filteredQuery($request)->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Login History');

        $headers = $this->exportHeaders();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        $row = 2;
        foreach ($logs as $log) {
            $sheet->fromArray($this->exportRow($log), null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'login-history-'.now()->format('Ymd-His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $logs = $this->filteredQuery($request)->get();
        $headers = $this->exportHeaders();
        $rows = $logs->map(fn ($log) => $this->exportRow($log));
        $judul = 'Login History';

        $pdf = Pdf::loadView('rekap.pdf-generik', compact('headers', 'rows', 'judul'))->setPaper('a4', 'landscape');

        return $pdf->download('login-history-'.now()->format('Ymd-His').'.pdf');
    }
}
