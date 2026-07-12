<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use DateTimeImmutable;
use NetCode\Identity\Application\Commands\EnableTwoFactor\EnableTwoFactor;
use NetCode\Identity\Application\Commands\EnableTwoFactor\EnableTwoFactorHandler;
use NetCode\Identity\Application\TwoFactorPolicy;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Infrastructure\Security\Sha256TokenHasher;
use NetCode\Identity\Tests\Support\FakeRecoveryCodeGenerator;
use NetCode\Identity\Tests\Support\FakeSecretEncrypter;
use NetCode\Identity\Tests\Support\FakeTotp;
use NetCode\Identity\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnableTwoFactorHandlerTest extends TestCase
{
    #[Test]
    public function it_enrols_pending_two_factor_and_returns_the_secret_and_codes(): void
    {
        $id = UserId::random();
        $users = new InMemoryUserRepository;
        $users->save(User::register(
            id: $id,
            realmId: null,
            email: new Email('ada@example.test'),
            name: 'Ada',
            passwordHash: 'hash',
            now: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        ));

        $handler = new EnableTwoFactorHandler(
            tokenHasher: new Sha256TokenHasher,
            totp: new FakeTotp,
            issuer: 'Acme',
            users: $users,
            secrets: new FakeSecretEncrypter,
            recoveryCodes: new FakeRecoveryCodeGenerator,
        );

        $enrolment = $handler(new EnableTwoFactor(userId: $id));

        $this->assertSame(FakeTotp::SECRET, $enrolment->secret);
        $this->assertStringContainsString('Acme', $enrolment->otpAuthUri);
        $this->assertCount(TwoFactorPolicy::RECOVERY_CODE_COUNT, $enrolment->recoveryCodes);

        $settings = $users->getById($id)->twoFactor();
        $this->assertNotNull($settings);
        $this->assertFalse($settings->isConfirmed());
        $this->assertSame('enc:'.FakeTotp::SECRET, $settings->secret);
        $this->assertContains(hash('sha256', $enrolment->recoveryCodes[0]), $settings->recoveryCodes);
        $this->assertNotContains($enrolment->recoveryCodes[0], $settings->recoveryCodes);
    }
}
