<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Command\ConfirmTwoFactor;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;
use NetCode\Identity\Domain\ValueObjects\UserId;

/** @implements Command<null> */
#[HandledBy(ConfirmTwoFactorHandler::class)]
final readonly class ConfirmTwoFactor implements Command
{
    public function __construct(
        public UserId $userId,
        public string $code,
    ) {}
}
