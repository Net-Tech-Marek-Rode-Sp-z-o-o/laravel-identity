<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Command\RegenerateRecoveryCodes;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;
use NetCode\Identity\Domain\ValueObjects\UserId;

/** @implements Command<RecoveryCodes> */
#[HandledBy(RegenerateRecoveryCodesHandler::class)]
final readonly class RegenerateRecoveryCodes implements Command
{
    public function __construct(
        public UserId $userId,
    ) {}
}
