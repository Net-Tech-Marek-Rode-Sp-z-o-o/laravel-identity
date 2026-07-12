<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use Illuminate\Support\Str;
use NetCode\Identity\Application\Ports\ChallengeTokenFactory;
use NetCode\Identity\Domain\ValueObjects\UserId;

final class FakeChallengeTokenFactory implements ChallengeTokenFactory
{
    private const string PREFIX = 'challenge:';

    public function issue(UserId $userId): string
    {
        return self::PREFIX.$userId->value();
    }

    public function verify(string $challengeToken): UserId|null
    {
        if (! Str::startsWith($challengeToken, self::PREFIX)) {
            return null;
        }

        return UserId::fromString(Str::after($challengeToken, self::PREFIX));
    }
}
