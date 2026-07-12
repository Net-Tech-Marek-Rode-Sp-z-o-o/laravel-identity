<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Command\DisableTwoFactor;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;
use NetCode\Identity\Domain\ValueObjects\UserId;

/** @implements Command<null> */
#[HandledBy(DisableTwoFactorHandler::class)]
final readonly class DisableTwoFactor implements Command
{
    public function __construct(
        public UserId $userId,
        public string $code,
    ) {}
}
