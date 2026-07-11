<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Port;

interface PasswordHasher
{
    public function hash(string $plain): string;

    public function verify(string $plain, string $hash): bool;
}
