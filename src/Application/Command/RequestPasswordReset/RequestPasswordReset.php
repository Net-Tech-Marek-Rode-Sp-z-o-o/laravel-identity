<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Command\RequestPasswordReset;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;

/** @implements Command<null> */
#[HandledBy(RequestPasswordResetHandler::class)]
final readonly class RequestPasswordReset implements Command
{
    public function __construct(
        public string $email,
    ) {}
}
