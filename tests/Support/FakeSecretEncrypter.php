<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use Illuminate\Support\Str;
use NetCode\Identity\Application\Ports\SecretEncrypter;

final class FakeSecretEncrypter implements SecretEncrypter
{
    private const string PREFIX = 'enc:';

    public function encrypt(string $plain): string
    {
        return self::PREFIX.$plain;
    }

    public function decrypt(string $cipher): string
    {
        return Str::after($cipher, self::PREFIX);
    }
}
