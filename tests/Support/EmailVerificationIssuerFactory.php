<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Application\EmailVerificationIssuer;
use NetCode\Identity\Infrastructure\Security\Sha256TokenHasher;

final class EmailVerificationIssuerFactory
{
    public static function make(
        SpyEmailVerificationNotifier $notifier = new SpyEmailVerificationNotifier,
        InMemoryEmailVerificationTokenRepository $tokens = new InMemoryEmailVerificationTokenRepository,
    ): EmailVerificationIssuer {
        return new EmailVerificationIssuer(
            clock: new FixedClock,
            generator: new FixedTokenGenerator(token: 'the-token'),
            tokenHasher: new Sha256TokenHasher,
            notifier: $notifier,
            tokens: $tokens,
            ttlMinutes: 1440,
        );
    }
}
