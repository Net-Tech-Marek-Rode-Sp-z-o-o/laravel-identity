<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Command\EnableTwoFactor;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;
use NetCode\Identity\Domain\ValueObjects\UserId;

/** @implements Command<TwoFactorEnrolment> */
#[HandledBy(EnableTwoFactorHandler::class)]
final readonly class EnableTwoFactor implements Command
{
    public function __construct(
        public UserId $userId,
    ) {}
}
