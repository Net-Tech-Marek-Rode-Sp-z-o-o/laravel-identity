<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use DateTimeImmutable;
use NetCode\Identity\Application\Commands\ConfirmTwoFactor\ConfirmTwoFactor;
use NetCode\Identity\Application\Commands\ConfirmTwoFactor\ConfirmTwoFactorHandler;
use NetCode\Identity\Domain\Exceptions\InvalidTwoFactorCodeException;
use NetCode\Identity\Domain\Exceptions\TwoFactorNotEnrolledException;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\TwoFactorSettings;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Tests\Support\FakeSecretEncrypter;
use NetCode\Identity\Tests\Support\FakeTotp;
use NetCode\Identity\Tests\Support\FixedClock;
use NetCode\Identity\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfirmTwoFactorHandlerTest extends TestCase
{
    private function handler(InMemoryUserRepository $users): ConfirmTwoFactorHandler
    {
        return new ConfirmTwoFactorHandler(
            totp: new FakeTotp,
            clock: new FixedClock,
            users: $users,
            secrets: new FakeSecretEncrypter,
        );
    }

    private function userWithPending(UserId $id): InMemoryUserRepository
    {
        $user = User::register(
            id: $id,
            realmId: null,
            email: new Email('ada@example.test'),
            name: 'Ada',
            passwordHash: 'hash',
            now: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );
        $user->enableTwoFactor(TwoFactorSettings::pending(secret: 'enc:'.FakeTotp::SECRET, recoveryCodes: []));

        $users = new InMemoryUserRepository;
        $users->save($user);

        return $users;
    }

    #[Test]
    public function it_confirms_two_factor_with_a_valid_code(): void
    {
        $id = UserId::random();
        $users = $this->userWithPending($id);

        ($this->handler($users))(new ConfirmTwoFactor(userId: $id, code: FakeTotp::VALID_CODE));

        $this->assertTrue($users->getById($id)->hasActiveTwoFactor());
    }

    #[Test]
    public function it_rejects_an_invalid_code(): void
    {
        $id = UserId::random();
        $users = $this->userWithPending($id);

        $this->expectException(InvalidTwoFactorCodeException::class);

        ($this->handler($users))(new ConfirmTwoFactor(userId: $id, code: '000000'));
    }

    #[Test]
    public function it_rejects_confirmation_without_enrolment(): void
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

        $this->expectException(TwoFactorNotEnrolledException::class);

        ($this->handler($users))(new ConfirmTwoFactor(userId: $id, code: FakeTotp::VALID_CODE));
    }
}
