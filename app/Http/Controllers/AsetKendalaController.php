<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Aset;
use App\Models\AsetKendala;
use App\Models\HealthCheckForm;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\AsetKendalaDilaporkan;
use App\Notifications\AsetKendalaStatusDiupdate;
use Illuminate\Http\Request;

// Laporan kerusakan aset dari lapangan -- beda dari Monitoring Kendala yang
// sumbernya item checklist Health Check ("Not OK"), ini dilaporkan LANGSUNG
// oleh siapapun yang buka halaman Detail Aset (biasanya lewat scan QR di
// fisik perangkat), lengkap sama foto kerusakan. RBAC-nya subtree, sama
// persis kayak Monitoring Kendala -- non-admin cuma lihat/tindaklanjuti
// laporan dari uker sendiri + turunannya, tapi SIAPA AJA yang bisa lihat
// aset itu (lewat AsetPolicy::view, sama kayak show()/qrCode()) boleh lapor.
class AsetKendalaController extends Controller
{
    protected function scopedQuery(Request $request)
    {
        $query = AsetKendala::with(['aset.uker.petugasIt', 'reporter']);

        if ($request->user()->role !== 'admin') {
            $ukerBolehDiakses = Uker::descendantKodes($request->user()->uker_kode);
            $query->whereHas('aset', fn ($q) => $q->whereIn('uker_kode', $ukerBolehDiakses));
        }

        return $query->latest();
    }

    protected function filteredQuery(Request $request)
    {
        $query = $this->scopedQuery($request);

        if ($request->filled('uker_kode')) {
            $kumpulanKode = Uker::descendantKodes((int) $request->input('uker_kode'));
            $query->whereHas('aset', fn ($q) => $q->whereIn('uker_kode', $kumpulanKode));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $query;
    }

    public function index(Request $request)
    {
        $isAdmin = $request->user()->role === 'admin';
        $laporanList = $this->filteredQuery($request)->get();

        $totalKeseluruhan = $this->scopedQuery($request)->count();
        $totalBelum = $this->scopedQuery($request)->where('status', 'Belum Ditindaklanjuti')->count();
        $totalSelesai = $this->scopedQuery($request)->where('status', 'Selesai Diperbaiki')->count();

        $ukerFilterList = $isAdmin
            ? Uker::orderBy('nama')->get()
            : Uker::whereIn('kode', Uker::descendantKodes($request->user()->uker_kode))->orderBy('nama')->get();

        return view('monitoring.laporan-aset', compact(
            'laporanList', 'isAdmin', 'ukerFilterList', 'totalKeseluruhan', 'totalBelum', 'totalSelesai'
        ));
    }

    public function store(Request $request, Aset $aset)
    {
        $this->authorize('view', $aset);

        $validated = $request->validate([
            'deskripsi' => 'required|string|max:1000',
            // max:10240 (10MB), samain sama Dokumentasi Visual Health Check --
            // foto ini dikompres client-side (window.kompresFotoInput) sebelum
            // submit juga, jadi biasanya jauh di bawah ini; batas cuma jaring
            // pengaman kalau kompresi gagal.
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $fotoPath = $request->hasFile('foto') ? $request->file('foto')->store('kendala-aset', 'public') : null;

        $kendala = AsetKendala::create([
            'aset_id' => $aset->id,
            'deskripsi' => $validated['deskripsi'],
            'foto_path' => $fotoPath,
            'reported_by' => $request->user()->id,
        ]);

        ActivityLog::catat('aset_kendala', 'lapor', 1, "Laporan kerusakan baru buat aset {$aset->no_asset} oleh {$request->user()->name}");
        User::where('role', 'admin')->get()->each->notify(new AsetKendalaDilaporkan($kendala));

        return back()->with('status', 'Laporan kerusakan berhasil dikirim, terima kasih.');
    }

    // User cuma boleh update status laporan yang aset-nya ada di subtree-nya
    // sendiri -- kalau nyoba lewat request langsung ke laporan di luar itu,
    // ditolak 403, sama pola kayak MonitoringController::authorizeAksesItem.
    protected function authorizeAksesKendala(Request $request, AsetKendala $kendala): void
    {
        if ($request->user()->role === 'admin') {
            return;
        }

        $ukerKode = $kendala->aset?->uker_kode;
        if (! $ukerKode || ! in_array($ukerKode, Uker::descendantKodes($request->user()->uker_kode))) {
            abort(403, 'Anda tidak punya akses ke laporan ini.');
        }
    }

    public function updateStatus(Request $request, AsetKendala $kendala)
    {
        $this->authorizeAksesKendala($request, $kendala);

        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', HealthCheckForm::DAFTAR_STATUS_TINDAK_LANJUT),
            'catatan_admin' => 'nullable|string',
        ]);

        $kendala->update($validated);

        ActivityLog::catat(
            'aset_kendala',
            'update_status',
            1,
            "Status laporan kerusakan aset {$kendala->aset?->no_asset} diupdate jadi {$validated['status']}"
        );

        if ($request->user()->role === 'admin') {
            $kendala->reporter?->notify(new AsetKendalaStatusDiupdate($kendala));
        } else {
            User::where('role', 'admin')->get()->each->notify(new AsetKendalaStatusDiupdate($kendala));
        }

        return back()->with('status', 'Status laporan kerusakan berhasil diupdate.');
    }
}
