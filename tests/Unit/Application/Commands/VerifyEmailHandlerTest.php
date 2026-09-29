<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use DateTimeImmutable;
use NetCode\Identity\Application\Commands\VerifyEmail\VerifyEmail;
use NetCode\Identity\Application\Commands\VerifyEmail\VerifyEmailHandler;
use NetCode\Identity\Domain\EmailVerificationToken;
use NetCode\Identity\Domain\Events\EmailVerified;
use NetCode\Identity\Domain\Exceptions\InvalidVerificationTokenException;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\EmailVerificationTokenId;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Infrastructure\Security\Sha256TokenHasher;
use NetCode\Identity\Tests\Support\FixedClock;
use NetCode\Identity\Tests\Support\InMemoryEmailVerificationTokenRepository;
use NetCode\Identity\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class VerifyEmailHandlerTest extends TestCase
{
    private InMemoryUserRepository $users;

    private InMemoryEmailVerificationTokenRepository $tokens;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository;
        $this->tokens = new InMemoryEmailVerificationTokenRepository;
    }

    private function handler(): VerifyEmailHandler
    {
        return new VerifyEmailHandler(
            clock: new FixedClock,
            tokenHasher: new Sha256TokenHasher,
            users: $this->users,
            tokens: $this->tokens,
        );
    }

    private function seed(UserId $id, string $expiresAt = '2026-01-02T00:00:00+00:00'): void
    {
        $this->users->save(User::reconstitute(
            id: $id,
            realmId: null,
            email: new Email('ada@example.test'),
            name: 'Ada',
            passwordHash: 'hash',
            twoFactor: null,
            deletedAt: null,
        ));
        $this->tokens->save(EmailVerificationToken::issue(
            id: EmailVerificationTokenId::random(),
            userId: $id,
            tokenHash: hash('sha256', 'the-token'),
            expiresAt: new DateTimeImmutable($expiresAt),
        ));
    }

    #[Test]
    public function it_verifies_the_email_and_marks_the_token_used(): void
    {
        $id = UserId::random();
        $this->seed(id: $id);

        ($this->handler())(new VerifyEmail(token: 'the-token'));

        $this->assertTrue($this->users->getById($id)->isEmailVerified());
        $this->assertNotNull($this->tokens->findByHash(hash('sha256', 'the-token'))?->usedAt());
        $this->assertContainsOnlyInstancesOf(EmailVerified::class, $this->users->published);
    }

    #[Test]
    public function it_rejects_an_unknown_token(): void
    {
        $this->expectException(InvalidVerificationTokenException::class);

        ($this->handler())(new VerifyEmail(token: 'nope'));
    }

    #[Test]
    public function it_rejects_an_expired_token(): void
    {
        $this->seed(id: UserId::random(), expiresAt: '2025-12-31T23:00:00+00:00');

        $this->expectException(InvalidVerificationTokenException::class);

        ($this->handler())(new VerifyEmail(token: 'the-token'));
    }
}
