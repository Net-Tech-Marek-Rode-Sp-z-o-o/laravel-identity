<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use DateTimeImmutable;
use NetCode\Identity\Application\Commands\ResendEmailVerification\ResendEmailVerification;
use NetCode\Identity\Application\Commands\ResendEmailVerification\ResendEmailVerificationHandler;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Tests\Support\EmailVerificationIssuerFactory;
use NetCode\Identity\Tests\Support\InMemoryEmailVerificationTokenRepository;
use NetCode\Identity\Tests\Support\InMemoryUserRepository;
use NetCode\Identity\Tests\Support\SpyEmailVerificationNotifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ResendEmailVerificationHandlerTest extends TestCase
{
    private InMemoryUserRepository $users;

    private InMemoryEmailVerificationTokenRepository $tokens;

    private SpyEmailVerificationNotifier $notifier;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository;
        $this->tokens = new InMemoryEmailVerificationTokenRepository;
        $this->notifier = new SpyEmailVerificationNotifier;
    }

    private function handler(): ResendEmailVerificationHandler
    {
        return new ResendEmailVerificationHandler(
            users: $this->users,
            issuer: EmailVerificationIssuerFactory::make(notifier: $this->notifier, tokens: $this->tokens),
        );
    }

    private function seed(UserId $id, DateTimeImmutable|null $verifiedAt): void
    {
        $this->users->save(User::reconstitute(
            id: $id,
            realmId: null,
            email: new Email('ada@example.test'),
            name: 'Ada',
            passwordHash: 'hash',
            twoFactor: null,
            deletedAt: null,
            emailVerifiedAt: $verifiedAt,
        ));
    }

    #[Test]
    public function it_sends_a_new_token_to_an_unverified_user(): void
    {
        $id = UserId::random();
        $this->seed(id: $id, verifiedAt: null);

        ($this->handler())(new ResendEmailVerification(userId: $id));

        $this->assertSame(1, $this->notifier->sent);
        $this->assertSame(1, $this->tokens->count());
    }

    #[Test]
    public function it_does_nothing_for_a_verified_user(): void
    {
        $id = UserId::random();
        $this->seed(id: $id, verifiedAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'));

        ($this->handler())(new ResendEmailVerification(userId: $id));

        $this->assertSame(0, $this->notifier->sent);
        $this->assertSame(0, $this->tokens->count());
    }
}
