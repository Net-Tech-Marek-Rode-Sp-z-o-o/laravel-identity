<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\AcceptInvitation;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;

/** @implements Command<string> */
#[HandledBy(AcceptInvitationHandler::class)]
final readonly class AcceptInvitation implements Command
{
    public function __construct(
        public string $token,
        public string $name,
        public string $password,
    ) {}
}
