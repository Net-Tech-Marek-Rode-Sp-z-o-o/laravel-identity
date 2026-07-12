<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Invitation;

use NetCode\Identity\Application\Ports\AcceptedInvitation;
use NetCode\Identity\Application\Ports\InvitationAcceptanceHook;

final class NullInvitationAcceptanceHook implements InvitationAcceptanceHook
{
    public function afterAcceptance(AcceptedInvitation $invitation): void {}
}
