<?php

namespace App\Http\Controllers;

use App\Models\Uker;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('ukerRelasi')->orderBy('name')->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $ukerList = Uker::orderBy('nama')->get();

        return view('users.create', compact('ukerList'));
    }

    protected function rules(Request $request, ?User $user = null): array
    {
        return [
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user?->id)],
            'pn' => ['nullable', 'string', 'max:50', 'exists:pekerja,pn', Rule::unique('users', 'pn')->ignore($user?->id)],
            'password' => $user ? 'nullable|string|min:8' : 'required|string|min:8',
            'role' => 'required|in:admin,user',
            'uker_kode' => $request->input('role') === 'user' ? 'required|integer|exists:ukers,kode' : 'nullable|integer|exists:ukers,kode',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules($request));

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'pn' => $validated['pn'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'uker_kode' => $validated['role'] === 'user' ? $validated['uker_kode'] : null,
        ]);

        return redirect()->route('users.index')->with('status', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $ukerList = Uker::orderBy('nama')->get();

        return view('users.edit', compact('user', 'ukerList'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate($this->rules($request, $user));

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->pn = $validated['pn'] ?? null;
        $user->role = $validated['role'];
        $user->uker_kode = $validated['role'] === 'user' ? $validated['uker_kode'] : null;
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        return redirect()->route('users.index')->with('status', 'User berhasil diupdate.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            abort(403, 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', 'User berhasil dihapus.');
    }
}
