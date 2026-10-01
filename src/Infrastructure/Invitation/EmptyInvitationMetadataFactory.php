<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Invitation;

use NetCode\Identity\Application\Ports\InvitationMetadataFactory;

final readonly class EmptyInvitationMetadataFactory implements InvitationMetadataFactory
{
    public function for(string $inviterId): array
    {
        return [];
    }
}
