<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

interface InvitationAcceptanceHook
{
    public function afterAcceptance(AcceptedInvitation $invitation): void;
}
