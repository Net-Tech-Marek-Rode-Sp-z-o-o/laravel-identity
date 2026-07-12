<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Port;

interface SecretEncrypter
{
    public function encrypt(string $plain): string;

    public function decrypt(string $cipher): string;
}
