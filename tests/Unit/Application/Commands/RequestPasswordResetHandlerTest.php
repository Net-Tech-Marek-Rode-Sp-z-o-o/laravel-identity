<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use NetCode\Identity\Application\Commands\RequestPasswordReset\RequestPasswordReset;
use NetCode\Identity\Application\Commands\RequestPasswordReset\RequestPasswordResetHandler;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Tests\Support\FixedClock;
use NetCode\Identity\Tests\Support\FixedRealmContext;
use NetCode\Identity\Tests\Support\FixedTokenGenerator;
use NetCode\Identity\Tests\Support\InMemoryPasswordResetTokenRepository;
use NetCode\Identity\Tests\Support\InMemoryUserRepository;
use NetCode\Identity\Tests\Support\SpyPasswordResetNotifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RequestPasswordResetHandlerTest extends TestCase
{
    private function handler(
        InMemoryUserRepository $users,
        InMemoryPasswordResetTokenRepository $tokens,
        SpyPasswordResetNotifier $notifier,
    ): RequestPasswordResetHandler {
        return new RequestPasswordResetHandler(
            clock: new FixedClock,
            realm: new FixedRealmContext,
            generator: new FixedTokenGenerator('the-token'),
            users: $users,
            notifier: $notifier,
            tokens: $tokens,
            ttlMinutes: 60,
        );
    }

    private function userRepositoryWithAda(): InMemoryUserRepository
    {
        $users = new InMemoryUserRepository;
        $users->save(User::reconstitute(
            id: UserId::random(),
            realmId: null,
            email: new Email('ada@example.test'),
            name: 'Ada',
            passwordHash: 'hash',
            twoFactor: null,
            deletedAt: null,
        ));

        return $users;
    }

    #[Test]
    public function it_issues_and_notifies_a_reset_token_for_a_known_user(): void
    {
        $tokens = new InMemoryPasswordResetTokenRepository;
        $notifier = new SpyPasswordResetNotifier;

        ($this->handler($this->userRepositoryWithAda(), $tokens, $notifier))(new RequestPasswordReset(
            email: 'ada@example.test',
        ));

        $this->assertSame('the-token', $notifier->token);
        $this->assertSame('ada@example.test', $notifier->email?->value());
        $this->assertNotNull($tokens->findByHash(hash('sha256', 'the-token')));
    }

    #[Test]
    public function it_is_silent_for_an_unknown_email(): void
    {
        $tokens = new InMemoryPasswordResetTokenRepository;
        $notifier = new SpyPasswordResetNotifier;

        ($this->handler(new InMemoryUserRepository, $tokens, $notifier))(new RequestPasswordReset(
            email: 'nobody@example.test',
        ));

        $this->assertNull($notifier->token);
        $this->assertNull($tokens->findByHash(hash('sha256', 'the-token')));
    }
}
