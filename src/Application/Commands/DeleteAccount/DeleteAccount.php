<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\DeleteAccount;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;
use NetCode\Identity\Domain\ValueObjects\UserId;

/** @implements Command<null> */
#[HandledBy(DeleteAccountHandler::class)]
final readonly class DeleteAccount implements Command
{
    public function __construct(
        public UserId $userId,
        public string|null $password,
    ) {}
}
