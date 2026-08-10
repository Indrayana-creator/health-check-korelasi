<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\KodeAset;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
}
