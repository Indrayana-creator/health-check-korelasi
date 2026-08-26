<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Uker;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('ukerRelasi')->orderBy('name')->paginate(20);

        $totalUser = User::count();
        $totalAdmin = User::where('role', 'admin')->count();
        $totalPetugas = $totalUser - $totalAdmin;

        return view('users.index', compact('users', 'totalUser', 'totalAdmin', 'totalPetugas'));
    }

    public function create()
    {
        // Dropdown pilihan uker di form ini dibatasi level KC ke atas --
        // yang punya akun login cuma kantor cabang, jadi assign user ke
        // level KCP/Unit gak relevan di sini (beda sama form Aset/Health
        // Check yang tetap harus bisa pilih semua level).
        $ukerList = Uker::levelKcKeAtas()->orderBy('nama')->get();

        return view('users.create', compact('ukerList'));
    }

    protected function rules(Request $request, ?User $user = null): array
    {
        return [
            'name' => 'required|string|max:150',
            'pn' => ['required', 'string', 'max:50', 'exists:pekerja,pn', Rule::unique('users', 'pn')->ignore($user?->id)],
            'password' => $user ? 'nullable|string|min:8' : 'required|string|min:8',
            'role' => 'required|in:admin,user',
            'uker_kode' => $request->input('role') === 'user' ? 'required|integer|exists:ukers,kode' : 'nullable|integer|exists:ukers,kode',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules($request));

        // Email gak lagi diminta dari form -- kolomnya nullable, gak
        // dipakai buat apapun yang fungsional (login pakai PN, gak ada
        // email verification/notifikasi beneran terkirim).
        $user = User::create([
            'name' => $validated['name'],
            'pn' => $validated['pn'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'uker_kode' => $validated['role'] === 'user' ? $validated['uker_kode'] : null,
        ]);
        ActivityLog::catat('user', 'tambah', 1, "User {$user->name} (PN {$user->pn}) ditambahkan");

        return redirect()->route('users.index')->with('status', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        // Sama kayak create() -- dibatasi level KC ke atas, TAPI kalau user
        // ini kebetulan sudah ter-assign ke uker di bawah KC (data lama),
        // tetap disertakan di daftar biar gak ke-ganti diam-diam pas form
        // disimpan tanpa disentuh.
        $ukerList = Uker::where(function ($q) use ($user) {
            $q->levelKcKeAtas();
            if ($user->uker_kode) {
                $q->orWhere('kode', $user->uker_kode);
            }
        })->orderBy('nama')->get();

        return view('users.edit', compact('user', 'ukerList'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate($this->rules($request, $user));

        // Email SENGAJA gak disentuh sama sekali di sini (bukan di-set ke
        // null) -- kalau user lama kebetulan masih punya email dari
        // sebelumnya, biarin tetap ada, cuma gak lagi diminta/diwajibkan.
        $user->name = $validated['name'];
        $user->pn = $validated['pn'] ?? null;
        $user->role = $validated['role'];
        $user->uker_kode = $validated['role'] === 'user' ? $validated['uker_kode'] : null;
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();
        ActivityLog::catat('user', 'update', 1, "User {$user->name} (PN {$user->pn}) diupdate");

        return redirect()->route('users.index')->with('status', 'User berhasil diupdate.');
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        $nama = $user->name;
        $pn = $user->pn;
        $user->delete();
        ActivityLog::catat('user', 'hapus', 1, "User {$nama} (PN {$pn}) dihapus");

        return redirect()->route('users.index')->with('status', 'User berhasil dihapus.');
    }

    // Nonaktifkan/aktifkan akun tanpa menghapus datanya -- user nonaktif
    // gak bisa login (lihat LoginRequest::authenticate()), tapi histori
    // aset/health check yang pernah dia buat tetap aman gak ikut hilang.
    public function toggleActive(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            abort(403, 'Anda tidak bisa menonaktifkan akun Anda sendiri.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $aksi = $user->is_active ? 'aktifkan' : 'nonaktifkan';
        ActivityLog::catat('user', $aksi, 1, "User {$user->name} (PN {$user->pn}) di-".($user->is_active ? 'aktifkan' : 'nonaktifkan'));

        return back()->with('status', $user->is_active ? 'User diaktifkan kembali.' : 'User dinonaktifkan.');
    }

    // Jaring pengaman buat pembatasan "1 PN cuma 1 sesi aktif" (lihat
    // LoginRequest::sesiLainAktif()) -- kalau device lama user gak jelas
    // nasibnya (HP hilang, lupa logout, dst) dan dia gak bisa/gak sempat
    // konfirmasi paksa-logout sendiri lewat alur /login/confirm, admin bisa
    // hapus manual semua sesi aktif user itu dari sini, biar dia bisa login
    // ulang di device baru tanpa nunggu SESSION_LIFETIME (120 menit) habis
    // sendiri.
    public function forceLogout(Request $request, User $user)
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();

        ActivityLog::catat('user', 'force_logout', 1, "Semua sesi aktif user {$user->name} (PN {$user->pn}) di-logout paksa oleh {$request->user()->name}");

        return back()->with('status', "Semua sesi aktif {$user->name} berhasil di-logout.");
    }

    // ===================== EXPORT =====================
    // Password SENGAJA gak pernah diikutkan (bukan cuma di-mask) -- gak ada
    // alasan yang sah buat itu keluar dari sistem sama sekali, meski cuma
    // dalam bentuk hash.

    protected function exportHeaders(): array
    {
        return ['Nama', 'PN', 'Role', 'Uker', 'Status'];
    }

    protected function exportRow(User $user): array
    {
        return [
            $user->name, $user->pn,
            $user->role === 'admin' ? 'Admin' : 'User', $user->ukerRelasi?->nama,
            $user->is_active ? 'Aktif' : 'Nonaktif',
        ];
    }

    public function exportExcel()
    {
        $users = User::with('ukerRelasi')->orderBy('name')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kelola User');

        $headers = $this->exportHeaders();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        $row = 2;
        foreach ($users as $u) {
            $sheet->fromArray($this->exportRow($u), null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'kelola-user-'.now()->format('Ymd-His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf()
    {
        $users = User::with('ukerRelasi')->orderBy('name')->get();
        $headers = $this->exportHeaders();
        $rows = $users->map(fn ($u) => $this->exportRow($u));
        $judul = 'Kelola User';

        $pdf = Pdf::loadView('rekap.pdf-generik', compact('headers', 'rows', 'judul'))->setPaper('a4', 'landscape');

        return $pdf->download('kelola-user-'.now()->format('Ymd-His').'.pdf');
    }
}
