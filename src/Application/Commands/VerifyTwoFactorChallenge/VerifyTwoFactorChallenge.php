<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\VerifyTwoFactorChallenge;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;

/** @implements Command<string> */
#[HandledBy(VerifyTwoFactorChallengeHandler::class)]
final readonly class VerifyTwoFactorChallenge implements Command
{
    public function __construct(
        public string $challengeToken,
        public string $code,
    ) {}
}
