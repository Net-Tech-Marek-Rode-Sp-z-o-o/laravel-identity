<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use DateTimeImmutable;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\TwoFactorSettings;
use NetCode\Identity\Domain\ValueObjects\UserId;

final class TwoFactorUserMother
{
    /**
     * A user with confirmed two-factor. Recovery codes are stored hashed, as in production.
     *
     * @param list<string> $plainRecoveryCodes
     */
    public static function confirmed(UserId $id, array $plainRecoveryCodes = []): User
    {
        $hashed = array_map(static fn (string $code): string => hash('sha256', $code), $plainRecoveryCodes);

        return User::reconstitute(
            id: $id,
            realmId: null,
            email: new Email('ada@example.test'),
            name: 'Ada',
            passwordHash: 'hash',
            twoFactor: new TwoFactorSettings(
                secret: 'enc:'.FakeTotp::SECRET,
                recoveryCodes: $hashed,
                confirmedAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            ),
            deletedAt: null,
        );
    }
}
