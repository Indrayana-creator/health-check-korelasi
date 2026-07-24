<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Aset;
use App\Models\KodeAset;
use App\Models\Uker;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AsetController extends Controller
{
    protected function rules(): array
    {
        $tahunSekarang = (int) date('Y');

        return [
            'uker_kode' => 'required|integer|exists:ukers,kode',
            'kode_aset_kode' => 'required|string|exists:kode_aset,kode',
            'merek' => 'required|string|max:100',
            'tipe_model' => 'required|string|max:100',
            'sn' => 'required|string|max:100',
            'kapasitas_memori' => 'nullable|string|max:50',
            'tahun_perolehan' => "nullable|integer|min:2000|max:{$tahunSekarang}",
            'kondisi' => 'nullable|in:' . implode(',', Aset::DAFTAR_KONDISI),
            'pemegang_nama' => 'nullable|string|max:150',
            'jabatan' => 'nullable|string|max:150',
            'pemegang_pn' => 'nullable|string|max:50',
            'ip_address' => 'nullable|string|max:50',
            'status_hardening' => 'nullable|string|max:50',
            'status_bitlocker' => 'nullable|string|max:50',
            'status_dlp' => 'nullable|string|max:50',
            'status_antivirus' => 'nullable|string|max:50',
            'keterangan' => 'nullable|string',
        ];
    }

    protected function scopedQuery(Request $request)
    {
        $query = Aset::with(['uker', 'kodeAset']);
        if ($request->user()->role !== 'admin') {
            $query->where('uker_kode', $request->user()->uker_kode);
        }
        return $query->orderBy('uker_kode');
    }

    protected function filteredQuery(Request $request)
    {
        $query = $this->scopedQuery($request);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('no_asset', 'like', "%{$q}%")
                    ->orWhere('merek', 'like', "%{$q}%")
                    ->orWhere('tipe_model', 'like', "%{$q}%")
                    ->orWhere('sn', 'like', "%{$q}%")
                    ->orWhere('pemegang_nama', 'like', "%{$q}%");
            });
        }

        if ($request->filled('uker_kode')) {
            $query->where('uker_kode', $request->input('uker_kode'));
        }

        return $query;
    }

    public function index(Request $request)
    {
        $asetList = $this->filteredQuery($request)->orderByDesc('id')->paginate(20)->withQueryString();
        $ukerFilterList = $request->user()->role === 'admin' ? Uker::orderBy('nama')->get() : collect();

        return view('aset.index', compact('asetList', 'ukerFilterList'));
    }

    public function create(Request $request)
    {
        $ukerList = $request->user()->role === 'admin'
            ? Uker::orderBy('nama')->get()
            : Uker::where('kode', $request->user()->uker_kode)->get();
        $kodeAsetList = KodeAset::orderBy('kategori')->orderBy('kode')->get();

        return view('aset.create', compact('ukerList', 'kodeAsetList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $this->authorize('assignToUker', [Aset::class, (int) $validated['uker_kode']]);

        $validated['no_asset'] = Aset::generateAsetId($validated['uker_kode'], $validated['kode_aset_kode']);

        Aset::create($validated);

        return redirect()->route('aset.index')->with('status', "Aset berhasil ditambahkan dengan ID {$validated['no_asset']}.");
    }

    public function edit(Request $request, Aset $aset)
    {
        $this->authorize('update', $aset);

        $ukerList = $request->user()->role === 'admin'
            ? Uker::orderBy('nama')->get()
            : Uker::where('kode', $request->user()->uker_kode)->get();
        $kodeAsetList = KodeAset::orderBy('kategori')->orderBy('kode')->get();

        return view('aset.edit', compact('aset', 'ukerList', 'kodeAsetList'));
    }

    public function update(Request $request, Aset $aset)
    {
        $this->authorize('update', $aset);

        $validated = $request->validate($this->rules());

        $this->authorize('assignToUker', [Aset::class, (int) $validated['uker_kode']]);

        // ASET ID gak diregenerate saat edit, biar ID-nya tetap sama sepanjang umur aset
        $aset->update($validated);

        return redirect()->route('aset.index')->with('status', 'Aset berhasil diupdate.');
    }

    public function destroy(Request $request, Aset $aset)
    {
        $this->authorize('delete', $aset);

        $aset->delete();

        return redirect()->route('aset.index')->with('status', 'Aset berhasil dihapus.');
    }

    // ===================== BULK UPLOAD (Excel) =====================

    public function bulkUploadForm(Request $request)
    {
        return view('aset.bulk-upload');
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

        // Kolom: A=uker_kode, B=kode_aset_kode, C=merek, D=tipe_model, E=sn,
        // F=no_asset (opsional, kalau kosong di-generate otomatis),
        // G=kapasitas_memori, H=tahun_perolehan, I=kondisi, J=pemegang_nama,
        // K=jabatan, L=pemegang_pn, M=ip_address, N=status_hardening,
        // O=status_bitlocker, P=status_dlp, Q=status_antivirus, R=keterangan
        for ($row = 2; $row <= $highestRow; $row++) {
            $ukerKode = $sheet->getCell("A{$row}")->getValue();
            $kodeAset = trim((string) $sheet->getCell("B{$row}")->getValue());

            if (!$ukerKode || !$kodeAset) {
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

            if (!KodeAset::where('kode', $kodeAset)->exists()) {
                $gagal[] = "Baris {$row}: kode aset {$kodeAset} tidak ditemukan di master";
                continue;
            }

            try {
                $noAsset = trim((string) $sheet->getCell("F{$row}")->getValue());
                if (!$noAsset) {
                    $noAsset = Aset::generateAsetId((int) $ukerKode, $kodeAset);
                }

                Aset::create([
                    'uker_kode' => (int) $ukerKode,
                    'kode_aset_kode' => $kodeAset,
                    'merek' => (string) $sheet->getCell("C{$row}")->getValue(),
                    'tipe_model' => (string) $sheet->getCell("D{$row}")->getValue(),
                    'sn' => (string) $sheet->getCell("E{$row}")->getValue(),
                    'no_asset' => $noAsset,
                    'kapasitas_memori' => (string) $sheet->getCell("G{$row}")->getValue() ?: null,
                    'tahun_perolehan' => is_numeric($sheet->getCell("H{$row}")->getValue()) ? (int) $sheet->getCell("H{$row}")->getValue() : null,
                    'kondisi' => (string) $sheet->getCell("I{$row}")->getValue() ?: null,
                    'pemegang_nama' => (string) $sheet->getCell("J{$row}")->getValue() ?: null,
                    'jabatan' => (string) $sheet->getCell("K{$row}")->getValue() ?: null,
                    'pemegang_pn' => (string) $sheet->getCell("L{$row}")->getValue() ?: null,
                    'ip_address' => (string) $sheet->getCell("M{$row}")->getValue() ?: null,
                    'status_hardening' => (string) $sheet->getCell("N{$row}")->getValue() ?: null,
                    'status_bitlocker' => (string) $sheet->getCell("O{$row}")->getValue() ?: null,
                    'status_dlp' => (string) $sheet->getCell("P{$row}")->getValue() ?: null,
                    'status_antivirus' => (string) $sheet->getCell("Q{$row}")->getValue() ?: null,
                    'keterangan' => (string) $sheet->getCell("R{$row}")->getValue() ?: null,
                ]);
                $berhasil++;
            } catch (\Throwable $e) {
                $gagal[] = "Baris {$row}: " . $e->getMessage();
            }
        }

        if ($berhasil > 0) {
            ActivityLog::catat('aset', 'upload_massal', $berhasil, "Upload massal dari file: {$namaFile}");
        }

        return back()->with('status', "Upload massal selesai: {$berhasil} aset berhasil ditambahkan.")
            ->with('gagal', $gagal);
    }

    // ===================== BULK DELETE (Excel) =====================

    public function bulkDeleteForm(Request $request)
    {
        return view('aset.bulk-delete');
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

        for ($row = 2; $row <= $highestRow; $row++) {
            $sn = trim((string) $sheet->getCell("A{$row}")->getValue());
            if (!$sn) {
                continue;
            }

            $query = Aset::where('sn', $sn);
            if (!$isAdmin) {
                $query->where('uker_kode', $ukerSendiri);
            }

            $aset = $query->first();
            if (!$aset) {
                $tidakKetemu[] = "Baris {$row}: SN {$sn} tidak ditemukan (atau bukan milik uker Anda)";
                continue;
            }

            $aset->delete();
            $terhapus++;
        }

        if ($terhapus > 0) {
            ActivityLog::catat('aset', 'delete_massal', $terhapus, "Delete massal dari file: {$namaFile}");
        }

        return back()->with('status', "Delete massal selesai: {$terhapus} aset berhasil dihapus.")
            ->with('gagal', $tidakKetemu);
    }

    // ===================== EXPORT =====================

    public function exportExcel(Request $request)
    {
        $asetList = $this->filteredQuery($request)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Aset');

        $headers = ['ASET ID', 'Uker', 'Kode Aset', 'Nama Perangkat', 'Merek', 'Type', 'SN', 'Kapasitas Memori', 'Tahun Distribusi', 'Umur (Tahun)', 'Sudah PH', 'Kondisi', 'Nama User', 'Jabatan', 'PN', 'IP Address', 'Hardening', 'Bitlocker', 'DLP', 'Antivirus', 'Keterangan'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:U1')->getFont()->setBold(true);

        $row = 2;
        foreach ($asetList as $aset) {
            $sheet->fromArray([
                $aset->no_asset,
                $aset->uker?->nama,
                $aset->kode_aset_kode,
                $aset->kodeAset?->nama,
                $aset->merek,
                $aset->tipe_model,
                $aset->sn,
                $aset->kapasitas_memori,
                $aset->tahun_perolehan,
                $aset->umur_tahun,
                $aset->sudah_ph ? 'Ya' : 'Tidak',
                $aset->kondisi,
                $aset->pemegang_nama,
                $aset->jabatan,
                $aset->pemegang_pn,
                $aset->ip_address,
                $aset->status_hardening,
                $aset->status_bitlocker,
                $aset->status_dlp,
                $aset->status_antivirus,
                $aset->keterangan,
            ], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'U') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'data-aset-' . now()->format('Ymd-His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $asetList = $this->filteredQuery($request)->get();

        $pdf = Pdf::loadView('aset.pdf', compact('asetList'))->setPaper('a4', 'landscape');

        return $pdf->download('data-aset-' . now()->format('Ymd-His') . '.pdf');
    }
}
