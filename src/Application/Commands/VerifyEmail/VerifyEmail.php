<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\VerifyEmail;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;

/** @implements Command<null> */
#[HandledBy(VerifyEmailHandler::class)]
final readonly class VerifyEmail implements Command
{
    public function __construct(
        public string $token,
    ) {}
}
