<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Port;

use NetCode\Identity\Domain\ValueObjects\UserId;

interface ChallengeTokenFactory
{
    public function issue(UserId $userId): string;

    public function verify(string $challengeToken): UserId|null;
}
