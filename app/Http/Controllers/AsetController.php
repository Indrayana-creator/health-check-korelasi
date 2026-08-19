<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Aset;
use App\Models\AsetEditRequest;
use App\Models\AsetKondisiLog;
use App\Models\KodeAset;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\AsetEditRequestDecided;
use App\Notifications\AsetEditRequestSubmitted;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AsetController extends Controller
{
    protected function rules(?Aset $aset = null): array
    {
        $tahunSekarang = (int) date('Y');

        return [
            'uker_kode' => 'required|integer|exists:ukers,kode',
            'kode_aset_kode' => 'required|string|exists:kode_aset,kode',
            'merek' => 'required|string|max:100',
            'tipe_model' => 'required|string|max:100',
            'sn' => [
                'required',
                'string',
                'max:100',
                // SN itu identitas fisik perangkat -- harus unik antar aset yang
                // masih aktif (belum di-soft-delete), biar bulk-delete by SN
                // (lihat bulkDelete()) gak ambigu nemuin aset yang salah.
                Rule::unique('aset', 'sn')
                    ->ignore($aset?->id)
                    ->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'kapasitas_memori' => 'nullable|string|max:50',
            'tahun_perolehan' => "nullable|integer|min:2000|max:{$tahunSekarang}",
            'kondisi' => 'required|in:'.implode(',', Aset::DAFTAR_KONDISI),
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

    protected function ruleMessages(): array
    {
        return [
            'sn.unique' => 'SN ini sudah dipakai oleh aset lain.',
        ];
    }

    protected function scopedQuery(Request $request)
    {
        $query = Aset::with(['uker', 'kodeAset']);
        if ($request->user()->role !== 'admin') {
            $query->whereIn('uker_kode', Uker::descendantKodes($request->user()->uker_kode));
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
            // Subtree, bukan exact-match -- filter ini juga dipakai jalan pintas
            // dari chart Struktur Organisasi yang angkanya emang akumulasi
            // 1 cabang + seluruh unit di bawahnya (konsisten sama cara RBAC
            // scoping di seluruh app), jadi pilih "KC X" harus ikut nampilin
            // aset yang tercatat di unit/KCP turunannya, bukan cuma persis di
            // kode KC itu sendiri (yang seringkali kosong).
            $query->whereIn('uker_kode', Uker::descendantKodes((int) $request->input('uker_kode')));
        }

        if ($request->filled('kategori')) {
            // Dipakai jalan pintas dari chart "Distribusi per Kategori" di
            // modal Struktur Organisasi -- kategori itu milik kode_aset,
            // bukan kolom langsung di tabel aset.
            $kategori = $request->input('kategori');
            $query->whereHas('kodeAset', fn ($q) => $q->where('kategori', $kategori));
        }

        if ($request->filled('kondisi')) {
            // Sentinel value, bukan salah satu isi Aset::DAFTAR_KONDISI -- buat
            // nyari aset lama (dibuat sebelum kolom ini wajib) yang kondisinya
            // masih kosong, dipakai jalan pintas dari chart Struktur Organisasi.
            if ($request->input('kondisi') === 'BELUM_DIISI') {
                $query->whereNull('kondisi');
            } else {
                $query->where('kondisi', $request->input('kondisi'));
            }
        }

        return $query;
    }

    // Kolom yang boleh dipakai buat sort lewat klik header tabel -- whitelist
    // ketat, biar 'sort' dari query string gak bisa dipakai buat nge-sort ke
    // kolom sembarangan (apalagi kolom yang gak ada di tabel aset).
    protected const KOLOM_BISA_DIURUTKAN = ['no_asset', 'merek', 'sn', 'tahun_perolehan', 'kondisi'];

    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);
        if (in_array($request->input('sort'), self::KOLOM_BISA_DIURUTKAN)) {
            $query->reorder($request->input('sort'), $request->input('dir') === 'desc' ? 'desc' : 'asc');
        } else {
            $query->reorder('id', 'desc');
        }
        $asetList = $query->paginate(20)->withQueryString();
        $ukerFilterList = $request->user()->role === 'admin' ? Uker::orderBy('nama')->get() : collect();

        // Ringkasan kondisi -- dihitung dari scope user (bukan hasil filter aktif),
        // biar stat card di atas nunjukin gambaran besar yang stabil sementara
        // tabel di bawahnya tetap ikut filter q/uker_kode/kondisi.
        $totalKeseluruhan = $this->scopedQuery($request)->count();
        $totalNormal = $this->scopedQuery($request)->where('kondisi', 'NORMAL')->count();
        $totalPerluPerhatian = $this->scopedQuery($request)->whereIn('kondisi', ['RUSAK', 'TIDAK LAYAK'])->count();

        return view('aset.index', compact('asetList', 'ukerFilterList', 'totalKeseluruhan', 'totalNormal', 'totalPerluPerhatian'));
    }

    public function create(Request $request)
    {
        $ukerList = $request->user()->role === 'admin'
            ? Uker::orderBy('nama')->get()
            : Uker::whereIn('kode', Uker::descendantKodes($request->user()->uker_kode))->orderBy('nama')->get();
        $kodeAsetList = KodeAset::orderBy('kategori')->orderBy('kode')->get();

        return view('aset.create', compact('ukerList', 'kodeAsetList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->ruleMessages());

        $this->authorize('assignToUker', [Aset::class, $validated['uker_kode']]);

        $validated['no_asset'] = Aset::generateAsetId($validated['uker_kode'], $validated['kode_aset_kode']);

        $aset = Aset::create($validated);
        ActivityLog::catat('aset', 'tambah', 1, "Aset {$aset->no_asset} ditambahkan");

        // Catatan pertama di riwayat kondisi -- kondisi_lama null nandain ini
        // baseline awal, bukan perubahan dari kondisi sebelumnya.
        AsetKondisiLog::create([
            'aset_id' => $aset->id,
            'kondisi_lama' => null,
            'kondisi_baru' => $aset->kondisi,
            'changed_by' => $request->user()->id,
        ]);

        return redirect()->route('aset.index')->with('status', "Aset berhasil ditambahkan dengan ID {$validated['no_asset']}.");
    }

    public function edit(Request $request, Aset $aset)
    {
        $this->authorize('update', $aset);

        $ukerList = $request->user()->role === 'admin'
            ? Uker::orderBy('nama')->get()
            : Uker::whereIn('kode', Uker::descendantKodes($request->user()->uker_kode))->orderBy('nama')->get();
        $kodeAsetList = KodeAset::orderBy('kategori')->orderBy('kode')->get();
        $bisaDiedit = $aset->bisaDiedit($request->user());
        $permintaanMenunggu = $aset->permintaanEditMenunggu();

        return view('aset.edit', compact('aset', 'ukerList', 'kodeAsetList', 'bisaDiedit', 'permintaanMenunggu'));
    }

    public function update(Request $request, Aset $aset)
    {
        $this->authorize('update', $aset);

        if (! $aset->bisaDiedit($request->user())) {
            abort(403, 'Data ini terkunci. Ajukan permintaan edit dan tunggu disetujui admin sebelum bisa mengubah data.');
        }

        $validated = $request->validate($this->rules($aset), $this->ruleMessages());

        $this->authorize('assignToUker', [Aset::class, $validated['uker_kode']]);

        $kondisiLama = $aset->kondisi;

        // ASET ID gak diregenerate saat edit, biar ID-nya tetap sama sepanjang umur aset
        $aset->update($validated);
        ActivityLog::catat('aset', 'update', 1, "Aset {$aset->no_asset} diupdate");

        if ($kondisiLama !== $aset->kondisi) {
            AsetKondisiLog::create([
                'aset_id' => $aset->id,
                'kondisi_lama' => $kondisiLama,
                'kondisi_baru' => $aset->kondisi,
                'changed_by' => $request->user()->id,
            ]);
        }

        // Kalau yang edit bukan admin, tandai izin edit yang dipakai barusan
        // biar "habis" -- gak bisa dipakai buat edit lagi tanpa ngajuin ulang
        if ($request->user()->role !== 'admin') {
            AsetEditRequest::where('aset_id', $aset->id)
                ->where('requested_by', $request->user()->id)
                ->where('status', 'Disetujui')
                ->where('sudah_dipakai', false)
                ->update(['sudah_dipakai' => true]);
        }

        return redirect()->route('aset.index')->with('status', 'Aset berhasil diupdate.');
    }

    public function requestEdit(Request $request, Aset $aset)
    {
        $this->authorize('update', $aset);

        $validated = $request->validate(['alasan' => 'nullable|string|max:255']);

        // Cegah ngajuin dobel kalau masih ada yang Menunggu
        $sudahAda = AsetEditRequest::where('aset_id', $aset->id)
            ->where('requested_by', $request->user()->id)
            ->where('status', 'Menunggu')
            ->exists();

        if (! $sudahAda) {
            $editRequest = AsetEditRequest::create([
                'aset_id' => $aset->id,
                'requested_by' => $request->user()->id,
                'alasan' => $validated['alasan'] ?? null,
                'status' => 'Menunggu',
            ]);

            User::where('role', 'admin')->get()->each->notify(new AsetEditRequestSubmitted($editRequest));
        }

        return redirect()->route('aset.edit', $aset)->with('status', 'Permintaan edit berhasil diajukan, menunggu approval admin.');
    }

    public function approveEdit(Request $request, AsetEditRequest $editRequest)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Hanya admin yang bisa approve permintaan edit.');
        }

        $editRequest->update([
            'status' => 'Disetujui',
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
        ]);
        ActivityLog::catat('aset', 'approve_edit', 1, "Permintaan edit aset {$editRequest->aset?->no_asset} disetujui");
        $editRequest->requester?->notify(new AsetEditRequestDecided($editRequest));

        return back()->with('status', 'Permintaan edit disetujui.');
    }

    public function rejectEdit(Request $request, AsetEditRequest $editRequest)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Hanya admin yang bisa menolak permintaan edit.');
        }

        $validated = $request->validate(['catatan_admin' => 'required|string']);

        $editRequest->update([
            'status' => 'Ditolak',
            'catatan_admin' => $validated['catatan_admin'],
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
        ]);
        ActivityLog::catat('aset', 'reject_edit', 1, "Permintaan edit aset {$editRequest->aset?->no_asset} ditolak");
        $editRequest->requester?->notify(new AsetEditRequestDecided($editRequest));

        return back()->with('status', 'Permintaan edit ditolak.');
    }

    public function destroy(Request $request, Aset $aset)
    {
        $this->authorize('delete', $aset);

        $noAsset = $aset->no_asset;
        $aset->delete();
        ActivityLog::catat('aset', 'hapus', 1, "Aset {$noAsset} dihapus");

        return redirect()->route('aset.index')->with('status', 'Aset berhasil dihapus. Bisa dipulihkan lewat halaman Sampah.');
    }

    // ===================== SAMPAH (soft delete) =====================
    // Aset yang dihapus gak langsung hilang permanen -- ditaruh di sini dulu
    // biar bisa dipulihkan kalau kehapus gak sengaja. Gak ada opsi hapus
    // permanen dari UI, sengaja, biar tetap ada jejaknya.

    public function trash(Request $request)
    {
        $query = Aset::onlyTrashed()->with(['uker', 'kodeAset']);
        if ($request->user()->role !== 'admin') {
            $query->whereIn('uker_kode', Uker::descendantKodes($request->user()->uker_kode));
        }

        $asetList = $query->orderByDesc('deleted_at')->paginate(20)->withQueryString();

        return view('aset.trash', compact('asetList'));
    }

    public function restore(Request $request, $id)
    {
        $aset = Aset::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $aset);

        $aset->restore();
        ActivityLog::catat('aset', 'restore', 1, "Aset {$aset->no_asset} dipulihkan dari sampah");

        return back()->with('status', "Aset {$aset->no_asset} berhasil dipulihkan.");
    }

    // Read-only -- gak pakai policy 'update' (yang ikut ngecek bisaDiedit()/
    // lock edit) karena lihat riwayat gak seharusnya sekeras itu, cukup
    // aset-nya emang ada di cakupan RBAC yang boleh dia lihat di daftar.
    public function kondisiRiwayat(Request $request, Aset $aset)
    {
        if ($request->user()->role !== 'admin' && ! in_array($aset->uker_kode, Uker::descendantKodes($request->user()->uker_kode))) {
            abort(403, 'Anda tidak punya akses ke riwayat aset ini.');
        }

        $logs = $aset->kondisiLogs()->with('changedBy')->get()->map(fn ($log) => [
            'kondisi_lama' => $log->kondisi_lama,
            'kondisi_baru' => $log->kondisi_baru,
            'changed_by' => $log->changedBy?->name,
            'created_at' => $log->created_at->format('d M Y H:i'),
        ]);

        return response()->json(['no_asset' => $aset->no_asset, 'logs' => $logs]);
    }

    // ===================== BULK UPLOAD (Excel) =====================

    public function bulkUploadForm(Request $request)
    {
        return view('aset.bulk-upload');
    }

    public function downloadTemplate(Request $request)
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Upload Aset');

        $headers = [
            'uker_kode', 'kode_aset_kode', 'merek', 'tipe_model', 'sn', 'no_asset',
            'kapasitas_memori', 'tahun_perolehan', 'kondisi', 'pemegang_nama', 'jabatan',
            'pemegang_pn', 'ip_address', 'status_hardening', 'status_bitlocker',
            'status_dlp', 'status_antivirus', 'keterangan',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:R1')->getFont()->setBold(true);

        // 1 baris contoh, biar jelas format isiannya -- bukan data sungguhan
        $sheet->fromArray([
            999, 'A1', 'Lenovo', 'ThinkPad T14', 'SN-CONTOH-001', '',
            '8GB', 2023, 'NORMAL', 'Contoh Nama', 'Staff', '00000',
            '10.0.0.1', 'Sudah', 'Aktif', 'Aktif', 'Aktif', 'Contoh baris, hapus sebelum upload data asli',
        ], null, 'A2');

        foreach (range('A', 'R') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'template_upload_aset.xlsx', [
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

        // Validasi header dulu -- kalau formatnya gak cocok sama template resmi,
        // tolak dari awal dengan pesan yang jelas, jangan diem-diem cuma "0 berhasil"
        $headerSeharusnya = [
            'uker_kode', 'kode_aset_kode', 'merek', 'tipe_model', 'sn', 'no_asset',
            'kapasitas_memori', 'tahun_perolehan', 'kondisi', 'pemegang_nama', 'jabatan',
            'pemegang_pn', 'ip_address', 'status_hardening', 'status_bitlocker',
            'status_dlp', 'status_antivirus', 'keterangan',
        ];
        $headerFile = [];
        foreach (range('A', 'R') as $col) {
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
        $snDalamFile = []; // lacak SN yang udah muncul di baris sebelumnya dalam file yang sama

        // Kolom: A=uker_kode, B=kode_aset_kode, C=merek, D=tipe_model, E=sn,
        // F=no_asset (opsional, kalau kosong di-generate otomatis),
        // G=kapasitas_memori, H=tahun_perolehan, I=kondisi, J=pemegang_nama,
        // K=jabatan, L=pemegang_pn, M=ip_address, N=status_hardening,
        // O=status_bitlocker, P=status_dlp, Q=status_antivirus, R=keterangan
        for ($row = 2; $row <= $highestRow; $row++) {
            $ukerKode = $sheet->getCell("A{$row}")->getValue();
            $kodeAset = trim((string) $sheet->getCell("B{$row}")->getValue());

            if (! $ukerKode || ! $kodeAset) {
                continue; // baris benar-benar kosong, dilewati diam-diam
            }

            if (! $isAdmin && ! in_array((int) $ukerKode, $ukerBolehDiakses)) {
                $gagal[] = "Baris {$row}: uker_kode {$ukerKode} bukan uker Anda atau cabang di bawahnya";

                continue;
            }

            $kodeAsetModel = KodeAset::where('kode', $kodeAset)->first();
            if (! $kodeAsetModel) {
                $gagal[] = "Baris {$row}: kode aset {$kodeAset} tidak ditemukan di master";

                continue;
            }

            if (! Uker::where('kode', $ukerKode)->exists()) {
                $gagal[] = "Baris {$row}: kode uker {$ukerKode} tidak ditemukan";

                continue;
            }

            // Baca semua kolom dulu
            $merek = trim((string) $sheet->getCell("C{$row}")->getValue());
            $tipeModel = trim((string) $sheet->getCell("D{$row}")->getValue());
            $sn = trim((string) $sheet->getCell("E{$row}")->getValue());
            $noAsset = trim((string) $sheet->getCell("F{$row}")->getValue());
            $kapasitasMemori = trim((string) $sheet->getCell("G{$row}")->getValue());
            $tahunPerolehan = $sheet->getCell("H{$row}")->getValue();
            $kondisi = trim((string) $sheet->getCell("I{$row}")->getValue());
            $pemegangNama = trim((string) $sheet->getCell("J{$row}")->getValue());
            $jabatan = trim((string) $sheet->getCell("K{$row}")->getValue());
            $pemegangPn = trim((string) $sheet->getCell("L{$row}")->getValue());
            $ipAddress = trim((string) $sheet->getCell("M{$row}")->getValue());
            $statusHardening = trim((string) $sheet->getCell("N{$row}")->getValue());
            $statusBitlocker = trim((string) $sheet->getCell("O{$row}")->getValue());
            $statusDlp = trim((string) $sheet->getCell("P{$row}")->getValue());
            $statusAntivirus = trim((string) $sheet->getCell("Q{$row}")->getValue());
            $keterangan = trim((string) $sheet->getCell("R{$row}")->getValue());

            // Validasi wajib: field dasar selalu wajib buat semua jenis aset
            $kolomWajibDasar = compact('merek', 'tipeModel', 'sn', 'kapasitasMemori', 'kondisi');
            $labelDasar = ['merek' => 'merek', 'tipeModel' => 'tipe_model', 'sn' => 'sn', 'kapasitasMemori' => 'kapasitas_memori', 'kondisi' => 'kondisi'];
            $kosong = [];
            foreach ($kolomWajibDasar as $key => $val) {
                if ($val === '') {
                    $kosong[] = $labelDasar[$key];
                }
            }
            if (! $tahunPerolehan) {
                $kosong[] = 'tahun_perolehan';
            }

            // Validasi wajib tambahan: khusus kategori yang "dipegang" 1 orang
            // (Personal Computer, Notebook, Tablet, Layar Monitor)
            if (in_array($kodeAsetModel->kategori, Aset::KATEGORI_PEMEGANG_INDIVIDU)) {
                $kolomWajibPemegang = compact('pemegangNama', 'jabatan', 'pemegangPn', 'ipAddress', 'statusHardening', 'statusBitlocker', 'statusDlp', 'statusAntivirus');
                $labelPemegang = [
                    'pemegangNama' => 'pemegang_nama', 'jabatan' => 'jabatan', 'pemegangPn' => 'pemegang_pn',
                    'ipAddress' => 'ip_address', 'statusHardening' => 'status_hardening',
                    'statusBitlocker' => 'status_bitlocker', 'statusDlp' => 'status_dlp', 'statusAntivirus' => 'status_antivirus',
                ];
                foreach ($kolomWajibPemegang as $key => $val) {
                    if ($val === '') {
                        $kosong[] = $labelPemegang[$key];
                    }
                }
            }

            if (! empty($kosong)) {
                $gagal[] = "Baris {$row}: kolom wajib masih kosong (".implode(', ', $kosong).')';

                continue;
            }

            // SN harus unik -- baik terhadap aset aktif yang udah ada di
            // database, maupun terhadap baris lain di file yang sama.
            if (isset($snDalamFile[$sn])) {
                $gagal[] = "Baris {$row}: SN {$sn} duplikat dengan baris {$snDalamFile[$sn]} di file ini";

                continue;
            }
            if (Aset::where('sn', $sn)->whereNull('deleted_at')->exists()) {
                $gagal[] = "Baris {$row}: SN {$sn} sudah dipakai oleh aset lain";

                continue;
            }
            $snDalamFile[$sn] = $row;

            try {
                if (! $noAsset) {
                    $noAsset = Aset::generateAsetId((int) $ukerKode, $kodeAset);
                }

                Aset::create([
                    'uker_kode' => (int) $ukerKode,
                    'kode_aset_kode' => $kodeAset,
                    'merek' => $merek,
                    'tipe_model' => $tipeModel,
                    'sn' => $sn,
                    'no_asset' => $noAsset,
                    'kapasitas_memori' => $kapasitasMemori,
                    'tahun_perolehan' => (int) $tahunPerolehan,
                    'kondisi' => $kondisi,
                    'pemegang_nama' => $pemegangNama ?: null,
                    'jabatan' => $jabatan ?: null,
                    'pemegang_pn' => $pemegangPn ?: null,
                    'ip_address' => $ipAddress ?: null,
                    'status_hardening' => $statusHardening ?: null,
                    'status_bitlocker' => $statusBitlocker ?: null,
                    'status_dlp' => $statusDlp ?: null,
                    'status_antivirus' => $statusAntivirus ?: null,
                    'keterangan' => $keterangan ?: null,
                ]);
                $berhasil++;
            } catch (\Throwable $e) {
                $gagal[] = "Baris {$row}: ".$e->getMessage();
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
        // Sama kayak bulkUpload() -- cuma dihitung buat non-admin, biar gak
        // crash mecoba descendantKodes(null) padahal admin selalu skip
        // pengecekan whereIn ini.
        $ukerBolehDiakses = $isAdmin ? [] : Uker::descendantKodes($request->user()->uker_kode);

        $terhapus = 0;
        $tidakKetemu = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $sn = trim((string) $sheet->getCell("A{$row}")->getValue());
            if (! $sn) {
                continue;
            }

            $query = Aset::where('sn', $sn);
            if (! $isAdmin) {
                $query->whereIn('uker_kode', $ukerBolehDiakses);
            }

            $aset = $query->first();
            if (! $aset) {
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

        $spreadsheet = new Spreadsheet;
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

        $filename = 'data-aset-'.now()->format('Ymd-His').'.xlsx';
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

        return $pdf->download('data-aset-'.now()->format('Ymd-His').'.pdf');
    }
}
