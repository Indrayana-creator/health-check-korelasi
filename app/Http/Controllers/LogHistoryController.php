<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LogHistoryController extends Controller
{
    protected function filteredQuery(Request $request)
    {
        $query = ActivityLog::with('user');

        if ($request->filled('tahun')) {
            $query->whereYear('created_at', $request->input('tahun'));
        }
        if ($request->filled('modul')) {
            $query->where('modul', $request->input('modul'));
        }
        if ($request->filled('aksi')) {
            $query->where('aksi', $request->input('aksi'));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        return $query->orderByDesc('created_at');
    }

    public function index(Request $request)
    {
        $logs = $this->filteredQuery($request)->paginate(20)->withQueryString();

        // Ringkasan tahun berjalan (atau tahun yang difilter), sesuai kebutuhan Pak Indra
        $tahunRingkasan = $request->input('tahun', now()->year);
        $ringkasan = ActivityLog::whereYear('created_at', $tahunRingkasan)
            ->selectRaw('modul, aksi, count(*) as jumlah_kejadian, sum(jumlah_baris) as total_baris')
            ->groupBy('modul', 'aksi')
            ->get();

        $tahunTersedia = ActivityLog::pluck('created_at')
            ->map(fn ($tanggal) => $tanggal->year)
            ->unique()
            ->sortDesc()
            ->values();

        // Opsi filter Aksi & User -- ditarik dari data yang beneran ada
        // (bukan daftar tetap yang bisa basi kalau modul baru nambah jenis
        // aksi baru), biar dropdown-nya selalu ngikut isi log yang nyata.
        // activity_logs cuma nambah (insert-only, gak pernah diubah/dihapus),
        // jadi di-cache 10 menit -- gak usah scan distinct ke tabel yang
        // terus membesar di SETIAP request halaman ini, cukup basi dikit gpp
        // buat isi dropdown filter.
        $aksiTersedia = Cache::remember('log-history:aksi-tersedia', now()->addMinutes(10), fn () => ActivityLog::distinct()->orderBy('aksi')->pluck('aksi'));
        $userIdTersedia = Cache::remember('log-history:user-id-tersedia', now()->addMinutes(10), fn () => ActivityLog::whereNotNull('user_id')->distinct()->pluck('user_id'));
        $userTersedia = User::whereIn('id', $userIdTersedia)->orderBy('name')->get();

        return view('log-history.index', compact('logs', 'ringkasan', 'tahunRingkasan', 'tahunTersedia', 'aksiTersedia', 'userTersedia'));
    }

    // ===================== EXPORT =====================

    protected function exportHeaders(): array
    {
        return ['Waktu', 'User', 'Modul', 'Aksi', 'Jumlah Baris', 'Keterangan', 'Baris Gagal'];
    }

    protected function exportRow(ActivityLog $log): array
    {
        return [
            $log->created_at->format('d-m-Y H:i'),
            $log->user?->name,
            $log->modul,
            $log->aksi,
            $log->jumlah_baris,
            $log->keterangan,
            $log->detail_gagal ? implode(' | ', $log->detail_gagal) : null,
        ];
    }

    public function exportExcel(Request $request)
    {
        $logs = $this->filteredQuery($request)->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Log History');

        $headers = $this->exportHeaders();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $row = 2;
        foreach ($logs as $log) {
            $sheet->fromArray($this->exportRow($log), null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'log-history-'.now()->format('Ymd-His').'.xlsx';
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

        $pdf = Pdf::loadView('log-history.pdf', compact('logs', 'headers'))->setPaper('a4', 'landscape');

        return $pdf->download('log-history-'.now()->format('Ymd-His').'.pdf');
    }
}
