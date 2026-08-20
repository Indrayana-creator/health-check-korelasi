<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\HealthCheckForm;
use App\Models\Pekerja;
use App\Models\Uker;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PekerjaController extends Controller
{
    public function index(Request $request)
    {
        $query = Pekerja::with('uker');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('pn', 'like', "%{$q}%");
            });
        }

        $pekerjaList = $query->orderBy('nama')->paginate(30)->withQueryString();

        return view('pekerja.index', compact('pekerjaList'));
    }

    public function create()
    {
        // Dropdown pilihan uker di form ini dibatasi level KC ke atas --
        // yang punya akun login cuma kantor cabang, jadi assign pekerja ke
        // level KCP/Unit gak relevan di sini (beda sama form Aset/Health
        // Check yang tetap harus bisa pilih semua level).
        $ukerList = Uker::levelKcKeAtas()->orderBy('nama')->get();

        return view('pekerja.create', compact('ukerList'));
    }

    protected function rules(Request $request, ?Pekerja $pekerja = null): array
    {
        // PN semestinya 8 digit angka -- wajib buat data BARU, tapi 1 akun
        // demo lama ("Budi Santoso", PN 00001) sengaja dipertahankan (sesuai
        // keputusan sebelumnya), jadi jangan ngeblokir edit ke akun itu
        // selama PN-nya sendiri gak disentuh.
        $pnRules = (! $pekerja || $request->input('pn') !== $pekerja->pn)
            ? ['digits:8']
            : [];

        return [
            'pn' => array_merge(['required', 'string', 'max:50'], $pnRules, [
                Rule::unique('pekerja', 'pn')->ignore($pekerja?->pn, 'pn'),
            ]),
            'nama' => 'required|string|max:150',
            'jabatan' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50',
            'uker_kode' => 'required|integer|exists:ukers,kode',
            // Nomor HP Indonesia -- diawali "08", boleh pakai strip (0812-3456-7890)
            // atau angka polos, total 10-14 digit. Dulu bebas ketik apa aja.
            'no_hp' => ['nullable', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (! $value) {
                    return;
                }
                $digitOnly = preg_replace('/[^0-9]/', '', $value);
                if (! preg_match('/^08[0-9]{8,12}$/', $digitOnly)) {
                    $fail('Format No HP tidak valid (harus diawali 08, contoh: 0812-3456-7890).');
                }
            }],
            'is_petugas_it' => 'nullable|boolean',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules($request));
        $validated['is_petugas_it'] = $request->boolean('is_petugas_it');

        Pekerja::create($validated);
        ActivityLog::catat('pekerja_uker', 'tambah', 1, "Pekerja {$validated['nama']} (PN {$validated['pn']}) ditambahkan");

        return redirect()->route('pekerja.index')->with('status', "Pekerja '{$validated['nama']}' (PN {$validated['pn']}) berhasil ditambahkan.");
    }

    public function edit(Pekerja $pekerja)
    {
        // Sama kayak create() -- dibatasi level KC ke atas, TAPI kalau
        // pekerja ini kebetulan sudah ter-assign ke uker di bawah KC
        // (data lama), tetap disertakan di daftar biar gak ke-ganti diam-
        // diam pas form disimpan tanpa disentuh.
        $ukerList = Uker::where(function ($q) use ($pekerja) {
            $q->levelKcKeAtas();
            if ($pekerja->uker_kode) {
                $q->orWhere('kode', $pekerja->uker_kode);
            }
        })->orderBy('nama')->get();

        return view('pekerja.edit', compact('pekerja', 'ukerList'));
    }

    public function update(Request $request, Pekerja $pekerja)
    {
        $validated = $request->validate($this->rules($request, $pekerja));
        $validated['is_petugas_it'] = $request->boolean('is_petugas_it');

        $pekerja->update($validated);
        ActivityLog::catat('pekerja_uker', 'update', 1, "Pekerja {$pekerja->nama} (PN {$pekerja->pn}) diupdate");

        return redirect()->route('pekerja.index')->with('status', 'Data pekerja berhasil diupdate.');
    }

    public function destroy(Pekerja $pekerja)
    {
        // pekerja.pn dipakai FK di users.pn & health_check_forms.pic_pn (keduanya
        // nullOnDelete) -- kalau dihapus sembarangan, PN login atau PIC form lama
        // bisa ke-null-kan diam-diam. Diblok dulu, harus dibereskan manual.
        if ($pekerja->user()->exists()) {
            return back()->with('status', 'Pekerja ini masih punya akun User terkait, tidak bisa dihapus. Hapus/ubah akun usernya dulu.');
        }
        if (HealthCheckForm::where('pic_pn', $pekerja->pn)->exists()) {
            return back()->with('status', 'Pekerja ini masih tercatat sebagai PIC di form Health Check, tidak bisa dihapus.');
        }

        $nama = $pekerja->nama;
        $pn = $pekerja->pn;
        $pekerja->delete();
        ActivityLog::catat('pekerja_uker', 'hapus', 1, "Pekerja {$nama} (PN {$pn}) dihapus");

        return redirect()->route('pekerja.index')->with('status', 'Pekerja berhasil dihapus.');
    }

    // ===================== EXPORT =====================

    protected function exportHeaders(): array
    {
        return ['PN', 'Nama', 'Jabatan', 'Uker', 'No HP', 'Petugas IT'];
    }

    protected function exportRow(Pekerja $pekerja): array
    {
        return [
            $pekerja->pn, $pekerja->nama, $pekerja->jabatan, $pekerja->uker?->nama,
            $pekerja->no_hp, $pekerja->is_petugas_it ? 'Ya' : 'Tidak',
        ];
    }

    public function exportExcel()
    {
        $pekerjaList = Pekerja::with('uker')->orderBy('nama')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kelola Pekerja');

        $headers = $this->exportHeaders();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        $row = 2;
        foreach ($pekerjaList as $p) {
            $sheet->fromArray($this->exportRow($p), null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'kelola-pekerja-'.now()->format('Ymd-His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf()
    {
        $pekerjaList = Pekerja::with('uker')->orderBy('nama')->get();
        $headers = $this->exportHeaders();
        $rows = $pekerjaList->map(fn ($p) => $this->exportRow($p));
        $judul = 'Kelola Pekerja';

        $pdf = Pdf::loadView('rekap.pdf-generik', compact('headers', 'rows', 'judul'))->setPaper('a4', 'landscape');

        return $pdf->download('kelola-pekerja-'.now()->format('Ymd-His').'.pdf');
    }
}
