<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\PermintaanPerangkat;
use App\Models\PermintaanPerangkatStatusLog;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\PermintaanPerangkatDiajukan;
use App\Notifications\PermintaanPerangkatStatusDiupdate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// "Permintaan Perangkat" -- cabang mengajukan permintaan perangkat/perbaikan
// ke admin, yang levelnya cukup KC/Cabang aja (BEDA dari Aset/HealthCheck
// yang scoping-nya subtree), jadi non-admin cuma exact match uker_kode
// sendiri, bukan Uker::descendantKodes().
class PermintaanPerangkatController extends Controller
{
    // Sama kayak scopedQuery()+filteredQuery() di MonitoringController --
    // dipakai bareng buat index() maupun export, biar hasil export SELALU
    // konsisten sama filter & scoping RBAC yang lagi aktif di tabelnya.
    protected function filteredQuery(Request $request)
    {
        $isAdmin = $request->user()->role === 'admin';

        // statusLogs dieager-load biar hariDiStatusIni()/sudahLama() (dipakai
        // buat badge SLA di tabel) gak nembak query per baris (N+1).
        $query = PermintaanPerangkat::with(['uker', 'requester', 'statusLogs'])->latest();

        if ($isAdmin) {
            if ($request->filled('uker_kode')) {
                $query->where('uker_kode', $request->input('uker_kode'));
            }
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
        } else {
            $query->where('uker_kode', $request->user()->uker_kode);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $isAdmin = $request->user()->role === 'admin';

        $permintaanList = $this->filteredQuery($request)->get();

        $totalKeseluruhan = $isAdmin ? PermintaanPerangkat::count() : $permintaanList->count();
        $totalPending = ($isAdmin ? PermintaanPerangkat::query() : PermintaanPerangkat::where('uker_kode', $request->user()->uker_kode))
            ->where('status', '!=', 'Done Terkirim')->count();
        $totalSelesai = $totalKeseluruhan - $totalPending;

        $ukerFilterList = $isAdmin ? Uker::orderBy('nama')->get() : collect();

        return view('permintaan-perangkat.index', compact(
            'permintaanList', 'isAdmin', 'ukerFilterList', 'totalKeseluruhan', 'totalPending', 'totalSelesai'
        ));
    }

    public function store(Request $request)
    {
        if ($request->user()->role === 'admin') {
            abort(403, 'Admin gak mengajukan permintaan perangkat, cuma bisa update status.');
        }

        $validated = $request->validate([
            'no_nota_dinas' => 'required|string|max:100',
            'fungsi_requester' => 'required|string|max:100',
            'jumlah' => 'required|integer|min:1',
            'keterangan' => 'required|string',
        ]);

        $permintaan = PermintaanPerangkat::create([
            ...$validated,
            'tanggal_request' => now()->toDateString(),
            'status' => PermintaanPerangkat::DAFTAR_STATUS[0],
            'uker_kode' => $request->user()->uker_kode,
            'requested_by' => $request->user()->id,
        ]);

        // Catatan pertama di riwayat status -- status_lama null nandain ini
        // baru diajukan, sama pola kayak AsetKondisiLog.
        PermintaanPerangkatStatusLog::create([
            'permintaan_perangkat_id' => $permintaan->id,
            'status_lama' => null,
            'status_baru' => $permintaan->status,
            'changed_by' => $request->user()->id,
        ]);

        ActivityLog::catat('permintaan_perangkat', 'ajukan', 1, "Permintaan perangkat {$permintaan->no_nota_dinas} diajukan oleh {$request->user()->name}");
        User::where('role', 'admin')->get()->each->notify(new PermintaanPerangkatDiajukan($permintaan));

        return redirect()->route('permintaan-perangkat.index')->with('status', 'Permintaan perangkat berhasil diajukan.')
            ->with('linkLacak', route('permintaan-perangkat.lacak', $permintaan->kode_lacak));
    }

    // Halaman cek status PUBLIK -- sengaja TANPA middleware auth, biar bisa
    // dibuka siapa aja yang punya link (kayak cek resi kurir), gak perlu
    // login/nunggu WA admin buat tau statusnya. Dicari lewat kode_lacak yang
    // acak (bukan id), biar gak bisa ditebak/di-enumerasi buat ngintip
    // permintaan cabang lain.
    public function lacak(string $kodeLacak)
    {
        // changedBy SENGAJA gak di-eager-load/ditampilin di halaman publik ini
        // -- siapa yang update status itu info internal, gak relevan buat
        // requester yang cuma mau tau progress-nya sampai mana.
        $permintaan = PermintaanPerangkat::with(['uker', 'statusLogs'])
            ->where('kode_lacak', $kodeLacak)
            ->firstOrFail();

        return view('permintaan-perangkat.lacak', compact('permintaan'));
    }

    public function updateStatus(Request $request, PermintaanPerangkat $permintaanPerangkat)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Hanya admin yang bisa update status permintaan perangkat.');
        }

        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', PermintaanPerangkat::DAFTAR_STATUS),
            'catatan_admin' => 'nullable|string',
        ]);

        $statusLama = $permintaanPerangkat->status;
        $permintaanPerangkat->update($validated);

        // Selalu dicatat, walau status-nya kebetulan gak berubah (misal admin
        // cuma mau nambah/update catatan) -- beda sama AsetKondisiLog yang
        // cuma log kalau kondisi BENERAN berubah, karena di sini catatan
        // admin sendiri (bukan cuma status) udah cukup jadi alasan buat
        // dicatat, dan kalau enggak, catatan lama bakal ketimpa tanpa jejak.
        PermintaanPerangkatStatusLog::create([
            'permintaan_perangkat_id' => $permintaanPerangkat->id,
            'status_lama' => $statusLama,
            'status_baru' => $validated['status'],
            'catatan_admin' => $validated['catatan_admin'] ?? null,
            'changed_by' => $request->user()->id,
        ]);

        ActivityLog::catat(
            'permintaan_perangkat',
            'update_status',
            1,
            "Status permintaan perangkat {$permintaanPerangkat->no_nota_dinas} diupdate jadi {$validated['status']}"
        );
        $permintaanPerangkat->requester?->notify(new PermintaanPerangkatStatusDiupdate($permintaanPerangkat));

        return back()->with('status', 'Status permintaan perangkat berhasil diupdate.');
    }

    // Bulk update status -- dipanggil dari checkbox di daftar Permintaan
    // Perangkat, admin milih banyak baris sekaligus lalu ubah status
    // bareng-bareng, daripada satu-satu lewat updateStatus() di atas.
    public function bulkUpdateStatus(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Hanya admin yang bisa update status permintaan perangkat.');
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:permintaan_perangkat,id',
            'status' => 'required|in:'.implode(',', PermintaanPerangkat::DAFTAR_STATUS),
        ]);

        $permintaanList = PermintaanPerangkat::whereIn('id', $validated['ids'])->get();
        foreach ($permintaanList as $permintaan) {
            $statusLama = $permintaan->status;
            $permintaan->update(['status' => $validated['status']]);

            PermintaanPerangkatStatusLog::create([
                'permintaan_perangkat_id' => $permintaan->id,
                'status_lama' => $statusLama,
                'status_baru' => $validated['status'],
                'changed_by' => $request->user()->id,
            ]);

            $permintaan->requester?->notify(new PermintaanPerangkatStatusDiupdate($permintaan));
        }

        ActivityLog::catat(
            'permintaan_perangkat',
            'bulk_update_status',
            $permintaanList->count(),
            "Update status massal {$permintaanList->count()} permintaan perangkat jadi {$validated['status']}"
        );

        return back()->with('status', "Status {$permintaanList->count()} permintaan berhasil diupdate jadi {$validated['status']}.");
    }

    // ===================== EXPORT =====================

    protected function exportHeaders(): array
    {
        return ['No Nota Dinas', 'Tanggal Request', 'Fungsi Requester', 'Jumlah', 'Status', 'Keterangan', 'Uker', 'Catatan Admin'];
    }

    protected function exportRow(PermintaanPerangkat $p): array
    {
        return [
            $p->no_nota_dinas,
            $p->tanggal_request?->format('Y-m-d'),
            $p->fungsi_requester,
            $p->jumlah,
            $p->status,
            $p->keterangan,
            $p->uker?->nama,
            $p->catatan_admin,
        ];
    }

    public function exportExcel(Request $request)
    {
        $permintaanList = $this->filteredQuery($request)->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Permintaan Perangkat');

        $headers = $this->exportHeaders();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        $row = 2;
        foreach ($permintaanList as $p) {
            $sheet->fromArray($this->exportRow($p), null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'permintaan-perangkat-'.now()->format('Ymd-His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $permintaanList = $this->filteredQuery($request)->get();
        $headers = $this->exportHeaders();

        $pdf = Pdf::loadView('permintaan-perangkat.pdf', compact('permintaanList', 'headers'))->setPaper('a4', 'landscape');

        return $pdf->download('permintaan-perangkat-'.now()->format('Ymd-His').'.pdf');
    }
}
