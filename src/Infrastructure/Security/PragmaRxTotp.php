<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Security;

use NetCode\Identity\Application\Ports\Totp;
use PragmaRX\Google2FA\Google2FA;

final readonly class PragmaRxTotp implements Totp
{
    public function __construct(
        private Google2FA $google2fa,
    ) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code) !== false;
    }

    public function provisioningUri(string $secret, string $accountName, string $issuer): string
    {
        return $this->google2fa->getQRCodeUrl($issuer, $accountName, $secret);
    }
}
