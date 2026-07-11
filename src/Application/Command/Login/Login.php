<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Command\Login;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;

/** @implements Command<string> */
#[HandledBy(LoginHandler::class)]
final readonly class Login implements Command
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
