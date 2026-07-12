<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\ResetPassword;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;

/** @implements Command<null> */
#[HandledBy(ResetPasswordHandler::class)]
final readonly class ResetPassword implements Command
{
    public function __construct(
        public string $token,
        public string $password,
    ) {}
}
