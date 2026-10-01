<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Application\Ports\InvitationAccess;
use NetCode\Identity\Application\Ports\InvitationSnapshot;

final readonly class AllowEveryoneInvitationAccess implements InvitationAccess
{
    public function allowsRevoke(string $requestedBy, InvitationSnapshot $invitation): bool
    {
        return true;
    }
}
