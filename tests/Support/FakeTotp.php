<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Application\Ports\Totp;

final class FakeTotp implements Totp
{
    public const string SECRET = 'FAKE-SECRET';

    public const string VALID_CODE = '123456';

    public function generateSecret(): string
    {
        return self::SECRET;
    }

    public function verify(string $secret, string $code): bool
    {
        return $code === self::VALID_CODE;
    }

    public function provisioningUri(string $secret, string $accountName, string $issuer): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$accountName).'?secret='.$secret.'&issuer='.rawurlencode($issuer);
    }
}
