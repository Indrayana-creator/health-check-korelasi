<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\HealthCheckForm;
use App\Models\Pekerja;
use App\Models\Uker;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    protected function rules(?Pekerja $pekerja = null): array
    {
        return [
            'pn' => ['required', 'string', 'max:50', Rule::unique('pekerja', 'pn')->ignore($pekerja?->pn, 'pn')],
            'nama' => 'required|string|max:150',
            'jabatan' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50',
            'uker_kode' => 'required|integer|exists:ukers,kode',
            'no_hp' => 'nullable|string|max:20',
            'is_petugas_it' => 'nullable|boolean',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
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
        $validated = $request->validate($this->rules($pekerja));
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
}
