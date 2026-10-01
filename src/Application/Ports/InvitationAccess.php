<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

interface InvitationAccess
{
    public function allowsRevoke(string $requestedBy, InvitationSnapshot $invitation): bool;
}
