<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Uker;
use App\Models\UkerPerubahanLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UkerController extends Controller
{
    public const DAFTAR_JENIS = ['KANWIL', 'AREA', 'KC', 'KCP', 'UNIT', 'LAINNYA'];

    public function index(Request $request)
    {
        $query = Uker::query();

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('kode', 'like', "%{$q}%");
            });
        }

        $ukers = $query->orderBy('nama')->paginate(30)->withQueryString();

        return view('ukers.index', compact('ukers'));
    }

    public function create()
    {
        $ukerIndukList = Uker::orderBy('nama')->get();

        return view('ukers.create', compact('ukerIndukList'));
    }

    protected function rules(?Uker $uker = null): array
    {
        return [
            'kode' => ['required', 'integer', Rule::unique('ukers', 'kode')->ignore($uker?->kode, 'kode')],
            'nama' => 'required|string|max:255',
            'alamat' => 'required|string|max:1000',
            'jenis' => 'required|in:'.implode(',', self::DAFTAR_JENIS),
            'kode_spv' => 'required|integer|exists:ukers,kode',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        // uker_spv otomatis diambil dari nama induk yang dipilih, biar konsisten
        $induk = Uker::where('kode', $validated['kode_spv'])->first();
        $validated['uker_spv'] = $induk?->nama;

        Uker::create($validated);
        ActivityLog::catat('uker', 'tambah', 1, "Uker {$validated['nama']} ({$validated['kode']}) ditambahkan");

        return redirect()->route('ukers.index')->with('status', "Uker/cabang '{$validated['nama']}' berhasil ditambahkan.");
    }

    public function edit(Uker $uker)
    {
        $ukerIndukList = Uker::where('kode', '!=', $uker->kode)->orderBy('nama')->get();
        $uker->load('perubahanLogs.changedBy');

        // Dipakai buat nerjemahin kode_spv lama/baru di Riwayat Perubahan jadi
        // nama uker -- ditarik SEKALI di sini (bukan query per baris log di
        // view) biar gak N+1 pas uker-nya punya banyak riwayat ganti induk.
        $ukerNamaMap = Uker::pluck('nama', 'kode');

        return view('ukers.edit', compact('uker', 'ukerIndukList', 'ukerNamaMap'));
    }

    public function update(Request $request, Uker $uker)
    {
        $validated = $request->validate($this->rules($uker));

        $induk = Uker::where('kode', $validated['kode_spv'])->first();
        $validated['uker_spv'] = $induk?->nama;

        // Riwayat perubahan -- 1 baris per FIELD yang beneran berubah (nilai
        // lama, DARI SEBELUM update()). uker_spv sengaja gak dicatat sendiri
        // (nilainya cuma turunan otomatis dari kode_spv di atas, nyatet
        // kode_spv aja udah cukup).
        foreach (['nama', 'jenis', 'alamat', 'kode_spv'] as $field) {
            if ((string) $uker->{$field} !== (string) $validated[$field]) {
                UkerPerubahanLog::create([
                    'uker_kode' => $uker->kode,
                    'field' => $field,
                    'nilai_lama' => $uker->{$field},
                    'nilai_baru' => $validated[$field],
                    'changed_by' => $request->user()->id,
                ]);
            }
        }

        $uker->update($validated);
        ActivityLog::catat('uker', 'update', 1, "Uker {$uker->nama} ({$uker->kode}) diupdate");

        return redirect()->route('ukers.index')->with('status', 'Data uker berhasil diupdate.');
    }

    public function destroy(Uker $uker)
    {
        if ($uker->aset()->exists() || $uker->pekerja()->exists()) {
            return back()->with('status', 'Uker ini masih punya data aset/pekerja terkait, tidak bisa dihapus.');
        }

        $nama = $uker->nama;
        $kode = $uker->kode;
        $uker->delete();
        ActivityLog::catat('uker', 'hapus', 1, "Uker {$nama} ({$kode}) dihapus");

        return redirect()->route('ukers.index')->with('status', 'Uker berhasil dihapus.');
    }

    // ===================== EXPORT =====================

    protected function exportHeaders(): array
    {
        return ['Kode', 'Nama', 'Jenis', 'Cabang Induk', 'Alamat'];
    }

    protected function exportRow(Uker $uker): array
    {
        return [$uker->kode, $uker->nama, $uker->jenis, $uker->uker_spv, $uker->alamat];
    }

    public function exportExcel()
    {
        $ukers = Uker::orderBy('nama')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kelola Uker');

        $headers = $this->exportHeaders();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        $row = 2;
        foreach ($ukers as $u) {
            $sheet->fromArray($this->exportRow($u), null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'kelola-uker-'.now()->format('Ymd-His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf()
    {
        $ukers = Uker::orderBy('nama')->get();
        $headers = $this->exportHeaders();
        $rows = $ukers->map(fn ($u) => $this->exportRow($u));
        $judul = 'Kelola Uker';

        $pdf = Pdf::loadView('rekap.pdf-generik', compact('headers', 'rows', 'judul'))->setPaper('a4', 'landscape');

        return $pdf->download('kelola-uker-'.now()->format('Ymd-His').'.pdf');
    }
}
