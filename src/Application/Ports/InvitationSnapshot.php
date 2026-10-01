<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

final readonly class InvitationSnapshot
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $id,
        public string|null $invitedBy,
        public array $metadata,
    ) {}
}
