<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

interface SecretEncrypter
{
    public function encrypt(string $plain): string;

    public function decrypt(string $cipher): string;
}
