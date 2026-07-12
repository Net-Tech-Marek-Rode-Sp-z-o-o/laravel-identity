<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

final readonly class AcceptedInvitation
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $userId,
        public string $email,
        public string|null $realmId,
        public array $metadata,
    ) {}
}
