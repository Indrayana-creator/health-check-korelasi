<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\DataImportService;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function form()
    {
        return view('import');
    }

    public function pekerja(Request $request, DataImportService $service)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $path = $request->file('file')->getRealPath();
        $namaFile = $request->file('file')->getClientOriginalName();
        $hasil = $service->importPekerjaFile($path);

        ActivityLog::catat(
            'pekerja_uker',
            'upload_massal',
            $hasil['pekerja_diproses'],
            "Import pekerja+uker dari file: {$namaFile} ({$hasil['uker_diproses']} uker, {$hasil['pekerja_diproses']} pekerja)"
        );

        return back()->with(
            'status',
            "Import pekerja+uker selesai: {$hasil['uker_diproses']} uker, {$hasil['pekerja_diproses']} pekerja diproses."
        );
    }

    public function petugasIt(Request $request, DataImportService $service)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $path = $request->file('file')->getRealPath();
        $namaFile = $request->file('file')->getClientOriginalName();
        $hasil = $service->importPetugasItFile($path);

        ActivityLog::catat(
            'pekerja_uker',
            'upload_massal',
            $hasil['ditandai_petugas_it'],
            "Update petugas IT dari file: {$namaFile}"
        );

        return back()->with(
            'status',
            "Tandai petugas IT selesai: {$hasil['ditandai_petugas_it']} ditandai, {$hasil['pn_tidak_ketemu']} PN gak ketemu di database pekerja."
        );
    }
}
