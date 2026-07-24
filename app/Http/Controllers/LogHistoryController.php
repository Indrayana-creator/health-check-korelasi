<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class LogHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        if ($request->filled('tahun')) {
            $query->whereYear('created_at', $request->input('tahun'));
        }
        if ($request->filled('modul')) {
            $query->where('modul', $request->input('modul'));
        }

        $logs = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        // Ringkasan tahun berjalan (atau tahun yang difilter), sesuai kebutuhan Pak Indra
        $tahunRingkasan = $request->input('tahun', now()->year);
        $ringkasan = ActivityLog::whereYear('created_at', $tahunRingkasan)
            ->selectRaw('modul, aksi, count(*) as jumlah_kejadian, sum(jumlah_baris) as total_baris')
            ->groupBy('modul', 'aksi')
            ->get();

        $tahunTersedia = ActivityLog::selectRaw('YEAR(created_at) as tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        return view('log-history.index', compact('logs', 'ringkasan', 'tahunRingkasan', 'tahunTersedia'));
    }
}
