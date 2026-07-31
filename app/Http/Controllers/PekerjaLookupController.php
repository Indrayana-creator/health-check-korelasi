<?php

namespace App\Http\Controllers;

use App\Models\Pekerja;
use Illuminate\Http\Request;

class PekerjaLookupController extends Controller
{
    // Dipanggil dari JS (fetch) di form Aset/Health Check, buat auto-isi
    // Uker begitu PN yang valid diketik. Read-only, gak expose data sensitif
    // selain nama uker & kodenya.
    public function lookupUker(string $pn)
    {
        $pekerja = Pekerja::with('uker')->find($pn);

        if (!$pekerja || !$pekerja->uker) {
            return response()->json(['message' => 'PN tidak ditemukan'], 404);
        }

        return response()->json([
            'pn' => $pekerja->pn,
            'nama' => $pekerja->nama,
            'uker_kode' => $pekerja->uker_kode,
            'uker_nama' => $pekerja->uker->nama,
        ]);
    }
}