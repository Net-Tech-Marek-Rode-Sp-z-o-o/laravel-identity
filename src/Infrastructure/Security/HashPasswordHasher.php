<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Security;

use Illuminate\Contracts\Hashing\Hasher;
use NetCode\Identity\Application\Port\PasswordHasher;

final readonly class HashPasswordHasher implements PasswordHasher
{
    public function __construct(
        private Hasher $hasher,
    ) {}

    public function hash(string $plain): string
    {
        return $this->hasher->make($plain);
    }

    public function verify(string $plain, string $hash): bool
    {
        return $this->hasher->check($plain, $hash);
    }
}
