<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\ResendEmailVerification;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;
use NetCode\Identity\Domain\ValueObjects\UserId;

/** @implements Command<null> */
#[HandledBy(ResendEmailVerificationHandler::class)]
final readonly class ResendEmailVerification implements Command
{
    public function __construct(
        public UserId $userId,
    ) {}
}
