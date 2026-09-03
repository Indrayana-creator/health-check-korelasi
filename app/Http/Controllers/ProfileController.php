<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\LoginLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        // Login History versi diri sendiri -- 15 baris terbaru cukup buat
        // user ngecek sendiri "beneran gue yang login ini semua?" tanpa
        // butuh halaman admin. Dicocokin PN JUGA (bukan cuma user_id), soalnya
        // percobaan gagal (salah password) kesimpen dengan PN yang diinput
        // walau belum tentu ke-resolve ke user_id manapun kalau PN-nya beda.
        $loginLogsSaya = LoginLog::where('user_id', $request->user()->id)
            ->orWhere('pn_dicoba', $request->user()->pn)
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return view('profile.edit', [
            'user' => $request->user(),
            'loginLogsSaya' => $loginLogsSaya,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}
