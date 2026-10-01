<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Application\Ports\InvitationMetadataFactory;

final readonly class FixedInvitationMetadataFactory implements InvitationMetadataFactory
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        private array $metadata,
    ) {}

    public function for(string $inviterId): array
    {
        return $this->metadata;
    }
}
