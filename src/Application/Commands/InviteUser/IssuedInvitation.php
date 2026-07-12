<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\InviteUser;

use DateTimeImmutable;

final readonly class IssuedInvitation
{
    public function __construct(
        public string $id,
        public string $email,
        public DateTimeImmutable $expiresAt,
    ) {}
}
