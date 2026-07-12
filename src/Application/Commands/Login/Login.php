<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\Login;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;

/** @implements Command<LoginResult> */
#[HandledBy(LoginHandler::class)]
final readonly class Login implements Command
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
