<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pn' => ['required', 'numeric'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // "Remember me" sengaja gak dipakai (selalu false) -- cookie
        // persisten dari situ bikin browser bisa login ulang sendiri TANPA
        // lewat pengecekan "sesi lain aktif" di bawah, karena login-via-
        // recaller-cookie itu jalan otomatis di middleware Laravel, gak
        // pernah nyentuh LoginRequest ini lagi.
        if (! Auth::attempt($this->only('pn', 'password'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'pn' => trans('auth.failed'),
            ]);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'pn' => 'Akun Anda sudah dinonaktifkan. Hubungi admin kalau ini keliru.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // 1 PN = 1 orang -- kalau akun ini kedapatan masih aktif di sesi lain
        // (belum expired), jangan langsung ambil alih diam-diam. Batalkan
        // login barusan, minta konfirmasi dulu lewat token sekali-pakai (biar
        // gak perlu re-input password lagi pas user beneran mau lanjut lewat
        // rute /login/confirm), kecuali PN itu dipakai lagi buat login normal
        // biasa (gak ada sesi lain aktif).
        $sesiLain = $this->sesiLainAktif($user);
        if ($sesiLain) {
            Auth::logout();

            $token = Str::random(40);
            Cache::put("login-confirm:{$token}", ['user_id' => $user->id], now()->addMinutes(3));

            $this->session()->flash('sesi_aktif_token', $token);
            $this->session()->flash('sesi_aktif_sejak', Carbon::createFromTimestamp($sesiLain->last_activity)->translatedFormat('d M Y, H:i'));

            throw ValidationException::withMessages([
                'sesi_aktif' => 'Akun ini masih aktif di perangkat lain.',
            ]);
        }
    }

    // Sesi LAIN (bukan punya request ini sendiri) milik user yang sama, yang
    // aktivitas terakhirnya masih dalam jendela SESSION_LIFETIME -- sesi yang
    // udah lewat jendela itu dianggap basi (bakal di-garbage-collect Laravel
    // sendiri), gak perlu dianggap "masih aktif" walau baris DB-nya belum
    // kehapus.
    protected function sesiLainAktif(User $user): ?object
    {
        $batasWaktu = now()->subMinutes((int) config('session.lifetime'))->timestamp;

        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $this->session()->getId())
            ->where('last_activity', '>=', $batasWaktu)
            ->orderByDesc('last_activity')
            ->first();
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'pn' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('pn')).'|'.$this->ip());
    }
}
