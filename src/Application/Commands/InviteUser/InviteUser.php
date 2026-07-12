<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\InviteUser;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;

/** @implements Command<IssuedInvitation> */
#[HandledBy(InviteUserHandler::class)]
final readonly class InviteUser implements Command
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $email,
        public array $metadata = [],
    ) {}
}
