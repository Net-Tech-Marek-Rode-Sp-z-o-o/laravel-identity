<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Security;

use NetCode\Identity\Application\Port\TokenGenerator;

final class RandomTokenGenerator implements TokenGenerator
{
    public function generate(): string
    {
        return bin2hex(random_bytes(32));
    }
}
