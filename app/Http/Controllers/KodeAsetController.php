<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\KodeAset;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class KodeAsetController extends Controller
{
    public function index(Request $request)
    {
        $query = KodeAset::query();

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('kode', 'like', "%{$q}%")
                    ->orWhere('kategori', 'like', "%{$q}%");
            });
        }

        $kodeAsetList = $query->orderBy('kategori')->orderBy('kode')->paginate(30)->withQueryString();

        // Daftar kategori yang udah pernah dipakai -- ditawarin sebagai saran
        // (datalist) di form tambah/edit, biar penulisan kategori konsisten
        // (gak ada "Personal Computer" vs "PERSONAL COMPUTER" nyampur).
        $kategoriTersedia = KodeAset::pluck('kategori')->unique()->sort()->values();

        return view('kode-aset.index', compact('kodeAsetList', 'kategoriTersedia'));
    }

    public function create()
    {
        $kategoriTersedia = KodeAset::pluck('kategori')->unique()->sort()->values();

        return view('kode-aset.create', compact('kategoriTersedia'));
    }

    protected function rules(?KodeAset $kodeAset = null): array
    {
        return [
            'kode' => ['required', 'string', 'max:20', Rule::unique('kode_aset', 'kode')->ignore($kodeAset?->kode, 'kode')],
            'kategori' => 'required|string|max:100',
            'nama' => 'required|string|max:150',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        KodeAset::create($validated);
        ActivityLog::catat('kode_aset', 'tambah', 1, "Kode Aset {$validated['kode']} ({$validated['nama']}) ditambahkan");

        return redirect()->route('kode-aset.index')->with('status', "Kode Aset '{$validated['nama']}' berhasil ditambahkan.");
    }

    public function edit(KodeAset $kodeAset)
    {
        $kategoriTersedia = KodeAset::pluck('kategori')->unique()->sort()->values();

        return view('kode-aset.edit', compact('kodeAset', 'kategoriTersedia'));
    }

    public function update(Request $request, KodeAset $kodeAset)
    {
        $validated = $request->validate($this->rules($kodeAset));

        $kodeAset->update($validated);
        ActivityLog::catat('kode_aset', 'update', 1, "Kode Aset {$kodeAset->kode} ({$kodeAset->nama}) diupdate");

        return redirect()->route('kode-aset.index')->with('status', 'Data Kode Aset berhasil diupdate.');
    }

    public function destroy(KodeAset $kodeAset)
    {
        if ($kodeAset->aset()->exists()) {
            return back()->with('status', 'Kode Aset ini masih dipakai oleh data aset, tidak bisa dihapus.');
        }

        $kode = $kodeAset->kode;
        $nama = $kodeAset->nama;
        $kodeAset->delete();
        ActivityLog::catat('kode_aset', 'hapus', 1, "Kode Aset {$kode} ({$nama}) dihapus");

        return redirect()->route('kode-aset.index')->with('status', 'Kode Aset berhasil dihapus.');
    }

    // ===================== EXPORT =====================

    protected function exportHeaders(): array
    {
        return ['Kode', 'Kategori', 'Nama'];
    }

    protected function exportRow(KodeAset $kodeAset): array
    {
        return [$kodeAset->kode, $kodeAset->kategori, $kodeAset->nama];
    }

    public function exportExcel()
    {
        $kodeAsetList = KodeAset::orderBy('kategori')->orderBy('kode')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kelola Kode Aset');

        $headers = $this->exportHeaders();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        $row = 2;
        foreach ($kodeAsetList as $k) {
            $sheet->fromArray($this->exportRow($k), null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'kelola-kode-aset-'.now()->format('Ymd-His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf()
    {
        $kodeAsetList = KodeAset::orderBy('kategori')->orderBy('kode')->get();
        $headers = $this->exportHeaders();
        $rows = $kodeAsetList->map(fn ($k) => $this->exportRow($k));
        $judul = 'Kelola Kode Aset';

        $pdf = Pdf::loadView('rekap.pdf-generik', compact('headers', 'rows', 'judul'))->setPaper('a4', 'landscape');

        return $pdf->download('kelola-kode-aset-'.now()->format('Ymd-His').'.pdf');
    }
}
