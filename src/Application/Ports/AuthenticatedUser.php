<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

final readonly class AuthenticatedUser
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string|null $realmId,
        public bool $emailVerified = false,
    ) {}
}
