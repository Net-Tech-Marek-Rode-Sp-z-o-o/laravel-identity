<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application;

use DateTimeImmutable;
use NetCode\Identity\Application\Command\ResetPassword\ResetPassword;
use NetCode\Identity\Application\Command\ResetPassword\ResetPasswordHandler;
use NetCode\Identity\Domain\Exception\InvalidResetTokenException;
use NetCode\Identity\Domain\PasswordResetToken;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\PasswordResetTokenId;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Tests\Support\FakePasswordHasher;
use NetCode\Identity\Tests\Support\FixedClock;
use NetCode\Identity\Tests\Support\InMemoryPasswordResetTokenRepository;
use NetCode\Identity\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ResetPasswordHandlerTest extends TestCase
{
    private const string CLOCK = '2026-01-01T00:00:00+00:00';

    private function handler(InMemoryUserRepository $users, InMemoryPasswordResetTokenRepository $tokens): ResetPasswordHandler
    {
        return new ResetPasswordHandler(
            clock: new FixedClock(new DateTimeImmutable(self::CLOCK)),
            hasher: new FakePasswordHasher,
            users: $users,
            tokens: $tokens,
        );
    }

    private function usersWith(UserId $id): InMemoryUserRepository
    {
        $users = new InMemoryUserRepository;
        $users->save(User::reconstitute(
            id: $id,
            realmId: null,
            email: new Email('ada@example.test'),
            name: 'Ada',
            passwordHash: 'hashed:old-password',
            twoFactor: null,
            deletedAt: null,
        ));

        return $users;
    }

    private function tokensWith(UserId $userId, DateTimeImmutable $expiresAt): InMemoryPasswordResetTokenRepository
    {
        $tokens = new InMemoryPasswordResetTokenRepository;
        $tokens->save(PasswordResetToken::issue(
            id: PasswordResetTokenId::random(),
            userId: $userId,
            tokenHash: hash('sha256', 'the-token'),
            expiresAt: $expiresAt,
        ));

        return $tokens;
    }

    #[Test]
    public function it_resets_the_password_and_marks_the_token_used(): void
    {
        $id = UserId::random();
        $users = $this->usersWith($id);
        $tokens = $this->tokensWith($id, new DateTimeImmutable('2026-01-01T01:00:00+00:00'));

        ($this->handler($users, $tokens))(new ResetPassword(
            token: 'the-token',
            password: 'new-password',
        ));

        $this->assertSame('hashed:new-password', $users->getById($id)->passwordHash());
        $this->assertNotNull($tokens->findByHash(hash('sha256', 'the-token'))?->usedAt());
    }

    #[Test]
    public function it_rejects_an_unknown_token(): void
    {
        $this->expectException(InvalidResetTokenException::class);

        ($this->handler(new InMemoryUserRepository, new InMemoryPasswordResetTokenRepository))(new ResetPassword(
            token: 'nonexistent',
            password: 'new-password',
        ));
    }

    #[Test]
    public function it_rejects_an_expired_token(): void
    {
        $id = UserId::random();
        $users = $this->usersWith($id);
        $tokens = $this->tokensWith($id, new DateTimeImmutable('2025-12-31T23:00:00+00:00'));

        $this->expectException(InvalidResetTokenException::class);

        ($this->handler($users, $tokens))(new ResetPassword(token: 'the-token', password: 'new-password'));
    }
}
