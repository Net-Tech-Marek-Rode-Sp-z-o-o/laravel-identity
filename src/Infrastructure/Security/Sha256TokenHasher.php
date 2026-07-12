<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Security;

use NetCode\Identity\Application\Ports\TokenHasher;

final class Sha256TokenHasher implements TokenHasher
{
    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
