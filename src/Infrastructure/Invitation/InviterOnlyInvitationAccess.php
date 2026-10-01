<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Invitation;

use NetCode\Identity\Application\Ports\InvitationAccess;
use NetCode\Identity\Application\Ports\InvitationSnapshot;

final readonly class InviterOnlyInvitationAccess implements InvitationAccess
{
    public function allowsRevoke(string $requestedBy, InvitationSnapshot $invitation): bool
    {
        return $invitation->invitedBy !== null && $invitation->invitedBy === $requestedBy;
    }
}
