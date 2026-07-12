<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Application\Ports\TokenIssuer;
use NetCode\Identity\Domain\ValueObjects\UserId;

final class FakeTokenIssuer implements TokenIssuer
{
    public UserId|null $issuedFor = null;

    public function issue(UserId $userId): string
    {
        $this->issuedFor = $userId;

        return 'token-'.$userId->value();
    }
}
