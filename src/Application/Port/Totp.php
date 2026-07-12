<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Port;

interface Totp
{
    public function generateSecret(): string;

    public function verify(string $secret, string $code): bool;

    public function provisioningUri(string $secret, string $accountName, string $issuer): string;
}
