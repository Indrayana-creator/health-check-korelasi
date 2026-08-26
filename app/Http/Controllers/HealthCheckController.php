<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\HealthCheckApprovalDecided;
use App\Notifications\HealthCheckItemFlaggedNotOk;
use App\Notifications\HealthCheckSubmittedForApproval;
use App\Support\PeriodeMingguan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class HealthCheckController extends Controller
{
    protected function scopedQuery(Request $request)
    {
        $query = HealthCheckForm::with(['uker', 'items']);
        if ($request->user()->role !== 'admin') {
            $query->whereIn('uker_kode', Uker::descendantKodes($request->user()->uker_kode));
        }

        return $query->orderBy('uker_kode');
    }

    protected function filteredQuery(Request $request)
    {
        $query = $this->scopedQuery($request);

        if ($request->filled('q')) {
            $query->where('periode', 'like', '%'.$request->input('q').'%');
        }

        if ($request->filled('uker_kode')) {
            // Subtree, bukan exact-match -- sama kayak fix di Data Aset &
            // Monitoring Kendala, biar link dari chart per-uker (mis. modal
            // Struktur Organisasi) yang milih "KC X" ikut nampilin form dari
            // unit turunannya, bukan cuma persis kode KC itu sendiri.
            $query->whereIn('uker_kode', Uker::descendantKodes((int) $request->input('uker_kode')));
        }

        if ($request->filled('status_approval')) {
            $query->where('status_approval', $request->input('status_approval'));
        }

        if ($request->boolean('dokumentasi_belum_lengkap')) {
            // Dipakai jalan pintas dari chart "Dokumentasi Visual" di
            // Dashboard -- form dianggap belum lengkap kalau salah satu dari
            // 3 foto wajib (Kategori E) masih kosong.
            $query->where(function ($sub) {
                foreach (array_keys(HealthCheckForm::FIELD_DOKUMENTASI_VISUAL) as $field) {
                    $sub->orWhereNull($field);
                }
            });
        }

        return $query;
    }

    // Whitelist kolom yang boleh di-sort lewat klik header tabel -- 'compliance'
    // gak masuk sini karena itu bukan kolom asli (dihitung dari relasi items),
    // butuh subquery kalau mau di-sort, belum sepadan efortnya sementara
    // kebutuhan "compliance terendah" udah kepenuhi lewat widget Ranking
    // Terendah di Dashboard.
    protected const KOLOM_BISA_DIURUTKAN = ['periode', 'tanggal_pemeriksaan'];

    public function index(Request $request)
    {
        $query = $this->filteredQuery($request)->withCount('items');
        if (in_array($request->input('sort'), self::KOLOM_BISA_DIURUTKAN)) {
            $query->reorder($request->input('sort'), $request->input('dir') === 'desc' ? 'desc' : 'asc');
        } else {
            $query->reorder('id', 'desc');
        }
        $formList = $query->paginate(20)->withQueryString();
        $ukerFilterList = $request->user()->role === 'admin' ? Uker::orderBy('nama')->get() : collect();

        // Ringkasan -- dihitung dari scope user (bukan hasil filter aktif),
        // biar stat card di atas nunjukin gambaran besar yang stabil sementara
        // tabel di bawahnya tetap ikut filter q/uker_kode/status_approval.
        $formsUntukStat = $this->scopedQuery($request)->get();
        $totalKeseluruhan = $formsUntukStat->count();
        $totalMenunggu = $formsUntukStat->where('status_approval', 'Menunggu Approval')->count();
        $totalItemSemua = $formsUntukStat->sum(fn ($f) => $f->items->count());
        $totalOkSemua = $formsUntukStat->sum(fn ($f) => $f->items->where('status', 'OK')->count());
        $avgCompliance = $totalItemSemua > 0 ? round($totalOkSemua / $totalItemSemua * 100, 1) : 0;

        return view('healthcheck.index', compact('formList', 'ukerFilterList', 'totalKeseluruhan', 'totalMenunggu', 'avgCompliance'));
    }

    public function create(Request $request)
    {
        $ukerList = $request->user()->role === 'admin'
            ? Uker::orderBy('nama')->get()
            : Uker::whereIn('kode', Uker::descendantKodes($request->user()->uker_kode))->orderBy('nama')->get();

        // Saran periode minggu kerja berjalan (Senin-Jumat) -- masih boleh
        // diubah manual, cuma nyaranin format yang gak ambigu biar gak perlu
        // hitung sendiri rentang tanggalnya.
        $periodeSaran = PeriodeMingguan::label(now());

        return view('healthcheck.create', compact('ukerList', 'periodeSaran'));
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
            'periode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('health_check_forms')
                    ->where(fn ($query) => $query->where('uker_kode', $request->input('uker_kode'))->whereNull('deleted_at')),
            ],
            'pic_pn' => 'nullable|string|max:50|exists:pekerja,pn',
        ], [
            'periode.unique' => 'Form health check untuk uker dan periode ini sudah ada.',
        ]);

        $this->authorize('assignToUker', [HealthCheckForm::class, $validated['uker_kode']]);

        $form = HealthCheckForm::create($validated);
        $this->generateItemsUntukForm($form);
        ActivityLog::catat('health_check', 'tambah', 1, "Form health check {$form->periode} dibuat untuk uker {$form->uker_kode}");

        return redirect()->route('healthcheck.edit', $form)->with('status', 'Form health check dibuat. Silakan isi status tiap item pemeriksaan.');
    }

    public function edit(Request $request, HealthCheckForm $healthcheck)
    {
        $this->authorize('update', $healthcheck);

        $itemsByKategori = $healthcheck->items()->orderBy('id')->get()->groupBy('kategori');

        return view('healthcheck.edit', compact('healthcheck', 'itemsByKategori'));
    }

    public function update(Request $request, HealthCheckForm $healthcheck)
    {
        $this->authorize('update', $healthcheck);

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:health_check_items,id',
            'items.*.status' => 'required|in:OK,Not OK,N/A,Belum Diperiksa',
            'items.*.catatan' => 'nullable|string',
            'items.*.foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'status_tindak_lanjut' => 'required|in:'.implode(',', HealthCheckForm::DAFTAR_STATUS_TINDAK_LANJUT),
            'catatan_tindak_lanjut' => 'nullable|string',
            'foto_ruang_server_url' => 'nullable|string|max:2048',
            'foto_storage_cctv_url' => 'nullable|string|max:2048',
            'foto_panel_ups_url' => 'nullable|string|max:2048',
        ]);

        $dataUpdate = [
            'status_tindak_lanjut' => $validated['status_tindak_lanjut'],
            'catatan_tindak_lanjut' => $validated['catatan_tindak_lanjut'] ?? null,
        ];

        // Item checklist (A-D) & dokumentasi visual (E) cuma boleh diubah
        // kalau form masih Draft/Ditolak -- dikunci bareng karena sama-sama
        // bagian dari bukti pemeriksaan. Kalau statusnya "Menunggu Approval"
        // atau "Disetujui", keduanya ditolak -- tapi status tindak lanjut
        // tetap boleh diupdate, karena remediasi berjalan terpisah dari
        // proses approval datanya.
        if ($healthcheck->itemsBisaDiedit()) {
            $jumlahBaruBermasalah = 0;
            foreach ($validated['items'] as $index => $itemInput) {
                $item = HealthCheckItem::where('id', $itemInput['id'])
                    ->where('health_check_form_id', $healthcheck->id)
                    ->first();

                if ($item && $item->status !== 'Not OK' && $itemInput['status'] === 'Not OK') {
                    $jumlahBaruBermasalah++;
                }

                $fotoPath = $item?->foto_path;
                // Foto lama dihapus dari disk begitu diganti yang baru -- biar
                // gak numpuk file yatim (foto_path di DB udah ketimpa, tapi
                // filenya masih nyangkut di storage) tiap kali item diedit ulang.
                if ($item && $request->hasFile("items.{$index}.foto")) {
                    if ($fotoPath) {
                        Storage::disk('public')->delete($fotoPath);
                    }
                    $fotoPath = $request->file("items.{$index}.foto")->store('healthcheck-item', 'public');
                }

                $item?->update([
                    'status' => $itemInput['status'],
                    'catatan' => $itemInput['catatan'] ?? null,
                    'foto_path' => $fotoPath,
                ]);
            }

            if ($jumlahBaruBermasalah > 0) {
                User::where('role', 'admin')->get()->each->notify(new HealthCheckItemFlaggedNotOk($healthcheck, $jumlahBaruBermasalah));
            }

            $dataUpdate['foto_ruang_server_url'] = $validated['foto_ruang_server_url'] ?? null;
            $dataUpdate['foto_storage_cctv_url'] = $validated['foto_storage_cctv_url'] ?? null;
            $dataUpdate['foto_panel_ups_url'] = $validated['foto_panel_ups_url'] ?? null;
        }

        $healthcheck->update($dataUpdate);
        ActivityLog::catat('health_check', 'update', 1, "Form health check {$healthcheck->periode} ({$healthcheck->uker?->nama}) diupdate");

        $pesan = $healthcheck->itemsBisaDiedit()
            ? 'Hasil pemeriksaan berhasil disimpan.'
            : 'Status tindak lanjut disimpan. Item checklist tidak diubah karena form sudah disubmit untuk approval.';

        return redirect()->route('healthcheck.index')->with('status', $pesan);
    }

    // ===================== APPROVAL WORKFLOW =====================

    public function submitForApproval(Request $request, HealthCheckForm $healthcheck)
    {
        $this->authorize('update', $healthcheck);

        if (! $healthcheck->itemsBisaDiedit()) {
            $pesan = $healthcheck->sudahLewatTanggal()
                ? 'Form ini sudah melewati tanggal pemeriksaannya, tidak bisa disubmit lagi.'
                : 'Form ini sudah disubmit sebelumnya.';
            abort(403, $pesan);
        }

        $healthcheck->update([
            'status_approval' => 'Menunggu Approval',
            'catatan_approval' => null,
        ]);
        ActivityLog::catat('health_check', 'submit', 1, "Form health check {$healthcheck->periode} ({$healthcheck->uker?->nama}) disubmit untuk approval");
        User::where('role', 'admin')->get()->each->notify(new HealthCheckSubmittedForApproval($healthcheck));

        return redirect()->route('healthcheck.index')->with('status', 'Form berhasil disubmit, menunggu approval admin.');
    }

    public function approve(Request $request, HealthCheckForm $healthcheck)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Hanya admin yang bisa approve form health check.');
        }

        if ($healthcheck->status_approval !== 'Menunggu Approval') {
            abort(403, 'Form ini tidak dalam status menunggu approval.');
        }

        $healthcheck->update([
            'status_approval' => 'Disetujui',
            'approved_by_pn' => $request->user()->pn,
            'approved_at' => now(),
        ]);
        ActivityLog::catat('health_check', 'approve', 1, "Form health check {$healthcheck->periode} ({$healthcheck->uker?->nama}) disetujui");
        User::where('uker_kode', $healthcheck->uker_kode)->get()->each->notify(new HealthCheckApprovalDecided($healthcheck));

        return redirect()->route('healthcheck.index')->with('status', 'Form health check disetujui.');
    }

    public function reject(Request $request, HealthCheckForm $healthcheck)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Hanya admin yang bisa menolak form health check.');
        }

        if ($healthcheck->status_approval !== 'Menunggu Approval') {
            abort(403, 'Form ini tidak dalam status menunggu approval.');
        }

        $validated = $request->validate([
            'catatan_approval' => 'required|string',
        ]);

        $healthcheck->update([
            'status_approval' => 'Ditolak',
            'catatan_approval' => $validated['catatan_approval'],
            'approved_by_pn' => $request->user()->pn,
            'approved_at' => now(),
        ]);
        ActivityLog::catat('health_check', 'reject', 1, "Form health check {$healthcheck->periode} ({$healthcheck->uker?->nama}) ditolak");
        User::where('uker_kode', $healthcheck->uker_kode)->get()->each->notify(new HealthCheckApprovalDecided($healthcheck));

        return redirect()->route('healthcheck.index')->with('status', 'Form health check ditolak, perlu direvisi.');
    }

    public function destroy(Request $request, HealthCheckForm $healthcheck)
    {
        $this->authorize('delete', $healthcheck);

        $periode = $healthcheck->periode;
        $ukerNama = $healthcheck->uker?->nama;
        $healthcheck->delete();
        ActivityLog::catat('health_check', 'hapus', 1, "Form health check {$periode} ({$ukerNama}) dihapus");

        return redirect()->route('healthcheck.index')->with('status', 'Form health check dihapus. Bisa dipulihkan lewat halaman Sampah.');
    }

    // ===================== SAMPAH (soft delete) =====================

    public function trash(Request $request)
    {
        $query = HealthCheckForm::onlyTrashed()->with('uker');
        if ($request->user()->role !== 'admin') {
            $query->whereIn('uker_kode', Uker::descendantKodes($request->user()->uker_kode));
        }

        $formList = $query->orderByDesc('deleted_at')->paginate(20)->withQueryString();

        return view('healthcheck.trash', compact('formList'));
    }

    public function restore(Request $request, $id)
    {
        $healthcheck = HealthCheckForm::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $healthcheck);

        $healthcheck->restore();
        ActivityLog::catat('health_check', 'restore', 1, "Form health check {$healthcheck->periode} ({$healthcheck->uker?->nama}) dipulihkan dari sampah");

        return back()->with('status', 'Form health check berhasil dipulihkan.');
    }

    // ===================== BULK UPLOAD (Excel) =====================
    // Tiap baris = 1 form baru (uker + tanggal + periode), seluruh item
    // checklist dari config('health_check_checklist') digenerate otomatis
    // persis seperti store() manual.

    public function bulkUploadForm(Request $request)
    {
        return view('healthcheck.bulk-upload');
    }

    public function downloadTemplate(Request $request)
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Upload Health Check');

        $headers = ['uker_kode', 'tanggal_pemeriksaan', 'periode', 'pic_pn'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        // 1 baris contoh, biar jelas format isiannya
        $sheet->fromArray([999, '2026-08-01', 'Agustus 2026', ''], null, 'A2');

        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'template_upload_healthcheck.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function bulkUpload(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        $namaFile = $request->file('file')->getClientOriginalName();
        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $headerSeharusnya = ['uker_kode', 'tanggal_pemeriksaan', 'periode', 'pic_pn'];
        $headerFile = [];
        foreach (range('A', 'D') as $col) {
            $headerFile[] = trim((string) $sheet->getCell("{$col}1")->getValue());
        }

        if ($headerFile !== $headerSeharusnya) {
            return back()->with('formatSalah', true);
        }

        $isAdmin = $request->user()->role === 'admin';
        // Cuma dihitung buat non-admin -- uker_kode admin selalu null, jadi
        // descendantKodes(null) bakal nge-crash (TypeError) kalau dipaksa
        // dihitung juga buat admin padahal gak pernah kepake ($isAdmin true
        // di bawah selalu skip pengecekan ini).
        $ukerBolehDiakses = $isAdmin ? [] : Uker::descendantKodes($request->user()->uker_kode);

        $berhasil = 0;
        $gagal = [];

        // Kolom: A=uker_kode, B=tanggal_pemeriksaan (YYYY-MM-DD), C=periode, D=pic_pn (opsional)
        for ($row = 2; $row <= $highestRow; $row++) {
            $ukerKode = $sheet->getCell("A{$row}")->getValue();
            $tanggal = $sheet->getCell("B{$row}")->getValue();
            $periode = trim((string) $sheet->getCell("C{$row}")->getValue());

            if (! $ukerKode || ! $tanggal || ! $periode) {
                continue;
            }

            if (! $isAdmin && ! in_array((int) $ukerKode, $ukerBolehDiakses)) {
                $gagal[] = "Baris {$row}: uker_kode {$ukerKode} bukan uker Anda atau cabang di bawahnya";

                continue;
            }

            if (! Uker::where('kode', $ukerKode)->exists()) {
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
                $gagal[] = "Baris {$row}: ".$e->getMessage();
            }
        }

        if ($berhasil > 0) {
            ActivityLog::catat('health_check', 'upload_massal', $berhasil, "Upload massal dari file: {$namaFile}", $gagal ?: null);
        }

        $totalItemPerForm = collect(config('health_check_checklist'))->flatten()->count();

        return back()->with('status', "Upload massal selesai: {$berhasil} form health check dibuat (masing-masing otomatis berisi {$totalItemPerForm} item checklist).")
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
        // Sama kayak bulkUpload() -- cuma dihitung buat non-admin, biar gak
        // crash mecoba descendantKodes(null) padahal admin selalu skip
        // pengecekan whereIn ini.
        $ukerBolehDiakses = $isAdmin ? [] : Uker::descendantKodes($request->user()->uker_kode);

        $terhapus = 0;
        $tidakKetemu = [];

        // Kolom: A=uker_kode, B=periode -- pasangan ini yang jadi kunci pencarian form
        for ($row = 2; $row <= $highestRow; $row++) {
            $ukerKode = $sheet->getCell("A{$row}")->getValue();
            $periode = trim((string) $sheet->getCell("B{$row}")->getValue());

            if (! $ukerKode || ! $periode) {
                continue;
            }

            if (! $isAdmin && ! in_array((int) $ukerKode, $ukerBolehDiakses)) {
                $tidakKetemu[] = "Baris {$row}: uker {$ukerKode} periode {$periode} tidak ditemukan (atau bukan uker Anda/cabang di bawahnya)";

                continue;
            }

            $form = HealthCheckForm::where('uker_kode', $ukerKode)->where('periode', $periode)->first();
            if (! $form) {
                $tidakKetemu[] = "Baris {$row}: uker {$ukerKode} periode {$periode} tidak ditemukan (atau bukan uker Anda/cabang di bawahnya)";

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

        $spreadsheet = new Spreadsheet;
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

        $filename = 'health-check-'.now()->format('Ymd-His').'.xlsx';
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

        return $pdf->download('health-check-'.now()->format('Ymd-His').'.pdf');
    }
}
