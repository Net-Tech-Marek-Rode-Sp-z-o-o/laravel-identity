<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\Register;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;
use NetCode\Identity\Application\Ports\RegistrationPayload;

/** @implements Command<string> */
#[HandledBy(RegisterHandler::class)]
final readonly class Register implements Command
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public RegistrationPayload $payload,
    ) {}
}
