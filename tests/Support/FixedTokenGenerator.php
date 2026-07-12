<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Application\Ports\TokenGenerator;

final class FixedTokenGenerator implements TokenGenerator
{
    public function __construct(
        private string $token = 'fixed-token',
    ) {}

    public function generate(): string
    {
        return $this->token;
    }
}
