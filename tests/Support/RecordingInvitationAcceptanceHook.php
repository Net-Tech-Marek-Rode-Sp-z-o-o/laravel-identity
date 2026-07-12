<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Application\Ports\AcceptedInvitation;
use NetCode\Identity\Application\Ports\InvitationAcceptanceHook;

final class RecordingInvitationAcceptanceHook implements InvitationAcceptanceHook
{
    public AcceptedInvitation|null $accepted = null;

    public function afterAcceptance(AcceptedInvitation $invitation): void
    {
        $this->accepted = $invitation;
    }
}
