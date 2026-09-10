<?php

namespace App\Services;

use App\Models\User;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
    protected Google2FA $engine;

    public function __construct()
    {
        $this->engine = new Google2FA;
    }

    public function generateSecretKey(): string
    {
        return $this->engine->generateSecretKey();
    }

    // "AsetSehat" sebagai issuer -- ini yang nongol di nama akun dalam
    // authenticator app (misal "AsetSehat (88888801)"), biar gampang
    // dibedain dari akun MFA lain di app yang sama.
    public function qrCodeDataUri(User $user, string $secret): string
    {
        $otpauthUrl = $this->engine->getQRCodeUrl(
            config('app.name'),
            $user->pn,
            $secret,
        );

        $builder = new Builder(
            writer: new PngWriter,
            data: $otpauthUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 240,
            margin: 8,
        );

        return $builder->build()->getDataUri();
    }

    public function verifyKey(string $secret, string $key): bool
    {
        return $this->engine->verifyKey($secret, $key, 1);
    }

    /**
     * @return array<int, string>
     */
    public function generateRecoveryCodes(int $jumlah = 8): array
    {
        return collect(range(1, $jumlah))
            ->map(fn () => Str::of(Str::random(10))->upper()->replace(['I', 'O', '0', '1'], ['J', 'P', '2', '3'])->toString())
            ->map(fn (string $kode) => substr($kode, 0, 5).'-'.substr($kode, 5, 5))
            ->all();
    }
}
