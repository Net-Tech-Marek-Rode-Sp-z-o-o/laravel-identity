<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Security;

use Illuminate\Contracts\Encryption\Encrypter;
use NetCode\Identity\Application\Ports\SecretEncrypter;

final readonly class LaravelSecretEncrypter implements SecretEncrypter
{
    public function __construct(
        private Encrypter $encrypter,
    ) {}

    public function encrypt(string $plain): string
    {
        return $this->encrypter->encrypt($plain);
    }

    public function decrypt(string $cipher): string
    {
        $value = $this->encrypter->decrypt($cipher);

        return is_string($value) ? $value : '';
    }
}
