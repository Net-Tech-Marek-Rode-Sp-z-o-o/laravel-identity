<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Port;

use NetCode\Identity\Domain\ValueObjects\UserId;

interface TokenIssuer
{
    public function issue(UserId $userId): string;
}
