<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

interface TokenHasher
{
    public function hash(string $token): string;
}
