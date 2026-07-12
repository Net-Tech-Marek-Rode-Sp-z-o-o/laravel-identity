<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Domain;

use DateTimeImmutable;
use NetCode\Identity\Domain\Event\TwoFactorDisabled;
use NetCode\Identity\Domain\Event\TwoFactorEnabled;
use NetCode\Identity\Domain\Exception\TwoFactorNotEnrolledException;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\TwoFactorSettings;
use NetCode\Identity\Domain\ValueObjects\UserId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TwoFactorTest extends TestCase
{
    private const string NOW = '2026-01-01T00:00:00+00:00';

    private function user(): User
    {
        $user = User::register(
            id: UserId::random(),
            realmId: null,
            email: new Email('ada@example.test'),
            name: 'Ada',
            passwordHash: 'hash',
            now: new DateTimeImmutable(self::NOW),
        );
        $user->releaseEvents();

        return $user;
    }

    #[Test]
    public function enrolment_is_pending_until_confirmed(): void
    {
        $user = $this->user();

        $user->enableTwoFactor(TwoFactorSettings::pending(secret: 'enc:SECRET', recoveryCodes: ['a', 'b']));

        $this->assertNotNull($user->twoFactor());
        $this->assertFalse($user->hasActiveTwoFactor());
        $this->assertCount(0, $user->releaseEvents());
    }

    #[Test]
    public function confirming_activates_two_factor_and_records_the_event(): void
    {
        $user = $this->user();
        $user->enableTwoFactor(TwoFactorSettings::pending(secret: 'enc:SECRET', recoveryCodes: []));

        $user->confirmTwoFactor(new DateTimeImmutable(self::NOW));

        $this->assertTrue($user->hasActiveTwoFactor());
        $events = $user->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TwoFactorEnabled::class, $events[0]);
    }

    #[Test]
    public function confirming_without_enrolment_is_rejected(): void
    {
        $this->expectException(TwoFactorNotEnrolledException::class);

        $this->user()->confirmTwoFactor(new DateTimeImmutable(self::NOW));
    }

    #[Test]
    public function disabling_clears_two_factor_and_records_the_event(): void
    {
        $user = $this->user();
        $user->enableTwoFactor(TwoFactorSettings::pending(secret: 'enc:SECRET', recoveryCodes: []));
        $user->confirmTwoFactor(new DateTimeImmutable(self::NOW));
        $user->releaseEvents();

        $user->disableTwoFactor(new DateTimeImmutable(self::NOW));

        $this->assertNull($user->twoFactor());
        $this->assertFalse($user->hasActiveTwoFactor());
        $events = $user->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TwoFactorDisabled::class, $events[0]);
    }

    #[Test]
    public function disabling_without_enrolment_is_rejected(): void
    {
        $this->expectException(TwoFactorNotEnrolledException::class);

        $this->user()->disableTwoFactor(new DateTimeImmutable(self::NOW));
    }

    #[Test]
    public function recovery_codes_can_be_matched_and_consumed(): void
    {
        $user = $this->user();
        $user->enableTwoFactor(TwoFactorSettings::pending(secret: 'enc:SECRET', recoveryCodes: ['aaa', 'bbb']));
        $user->confirmTwoFactor(new DateTimeImmutable(self::NOW));

        $this->assertTrue($user->hasRecoveryCode('aaa'));

        $user->consumeRecoveryCode('aaa');

        $this->assertFalse($user->hasRecoveryCode('aaa'));
        $this->assertTrue($user->hasRecoveryCode('bbb'));
    }

    #[Test]
    public function regenerating_recovery_codes_requires_confirmed_two_factor(): void
    {
        $user = $this->user();
        $user->enableTwoFactor(TwoFactorSettings::pending(secret: 'enc:SECRET', recoveryCodes: ['aaa']));

        $this->expectException(TwoFactorNotEnrolledException::class);

        $user->regenerateRecoveryCodes(['new']);
    }
}
