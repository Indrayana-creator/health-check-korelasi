<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\PermintaanPerangkat;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\PermintaanPerangkatDiajukan;
use App\Notifications\PermintaanPerangkatStatusDiupdate;
use Illuminate\Http\Request;

// "Permintaan Perangkat" -- cabang mengajukan permintaan perangkat/perbaikan
// ke admin, yang levelnya cukup KC/Cabang aja (BEDA dari Aset/HealthCheck
// yang scoping-nya subtree), jadi non-admin cuma exact match uker_kode
// sendiri, bukan Uker::descendantKodes().
class PermintaanPerangkatController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = $request->user()->role === 'admin';

        $query = PermintaanPerangkat::with(['uker', 'requester'])->latest();

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

        $permintaanList = $query->get();

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

        ActivityLog::catat('permintaan_perangkat', 'ajukan', 1, "Permintaan perangkat {$permintaan->no_nota_dinas} diajukan oleh {$request->user()->name}");
        User::where('role', 'admin')->get()->each->notify(new PermintaanPerangkatDiajukan($permintaan));

        return redirect()->route('permintaan-perangkat.index')->with('status', 'Permintaan perangkat berhasil diajukan.');
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

        $permintaanPerangkat->update($validated);
        ActivityLog::catat(
            'permintaan_perangkat',
            'update_status',
            1,
            "Status permintaan perangkat {$permintaanPerangkat->no_nota_dinas} diupdate jadi {$validated['status']}"
        );
        $permintaanPerangkat->requester?->notify(new PermintaanPerangkatStatusDiupdate($permintaanPerangkat));

        return back()->with('status', 'Status permintaan perangkat berhasil diupdate.');
    }
}
