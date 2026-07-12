<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

final readonly class RegisteredUser
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
    ) {}
}
