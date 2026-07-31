<?php

namespace App\Http\Controllers;

use App\Models\Uker;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'alamat' => 'nullable|string|max:1000',
            'jenis' => 'required|in:' . implode(',', self::DAFTAR_JENIS),
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

        return redirect()->route('ukers.index')->with('status', "Uker/cabang '{$validated['nama']}' berhasil ditambahkan.");
    }

    public function edit(Uker $uker)
    {
        $ukerIndukList = Uker::where('kode', '!=', $uker->kode)->orderBy('nama')->get();
        return view('ukers.edit', compact('uker', 'ukerIndukList'));
    }

    public function update(Request $request, Uker $uker)
    {
        $validated = $request->validate($this->rules($uker));

        $induk = Uker::where('kode', $validated['kode_spv'])->first();
        $validated['uker_spv'] = $induk?->nama;

        $uker->update($validated);

        return redirect()->route('ukers.index')->with('status', 'Data uker berhasil diupdate.');
    }

    public function destroy(Uker $uker)
    {
        if ($uker->aset()->exists() || $uker->pekerja()->exists()) {
            return back()->with('status', 'Uker ini masih punya data aset/pekerja terkait, tidak bisa dihapus.');
        }

        $uker->delete();

        return redirect()->route('ukers.index')->with('status', 'Uker berhasil dihapus.');
    }
}
