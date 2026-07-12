<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\RevokeInvitation;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;

/** @implements Command<null> */
#[HandledBy(RevokeInvitationHandler::class)]
final readonly class RevokeInvitation implements Command
{
    public function __construct(
        public string $invitationId,
    ) {}
}
