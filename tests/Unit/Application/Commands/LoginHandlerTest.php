<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use NetCode\Identity\Application\Commands\Login\Login;
use NetCode\Identity\Application\Commands\Login\LoginHandler;
use NetCode\Identity\Domain\Exceptions\InvalidCredentialsException;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\TwoFactorSettings;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Tests\Support\FakeChallengeTokenFactory;
use NetCode\Identity\Tests\Support\FakePasswordHasher;
use NetCode\Identity\Tests\Support\FakeTokenIssuer;
use NetCode\Identity\Tests\Support\FixedRealmContext;
use NetCode\Identity\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LoginHandlerTest extends TestCase
{
    private function handler(InMemoryUserRepository $repo): LoginHandler
    {
        return new LoginHandler(
            realm: new FixedRealmContext,
            tokens: new FakeTokenIssuer,
            users: $repo,
            hasher: new FakePasswordHasher,
            challenges: new FakeChallengeTokenFactory,
        );
    }

    private function withUser(
        string|null $passwordHash = 'hashed:secret123',
        TwoFactorSettings|null $twoFactor = null,
    ): InMemoryUserRepository {
        $repo = new InMemoryUserRepository;
        $repo->save(User::reconstitute(
            id: UserId::random(),
            realmId: null,
            email: new Email('ada@example.test'),
            name: 'Ada',
            passwordHash: $passwordHash,
            twoFactor: $twoFactor,
            deletedAt: null,
        ));

        return $repo;
    }

    #[Test]
    public function it_issues_a_token_for_valid_credentials(): void
    {
        $result = ($this->handler($this->withUser()))(new Login(
            email: 'ada@example.test',
            password: 'secret123',
        ));

        $this->assertFalse($result->requiresTwoFactor());
        $this->assertNotNull($result->token);
        $this->assertStringStartsWith('token-', $result->token);
    }

    #[Test]
    public function it_returns_a_challenge_when_two_factor_is_active(): void
    {
        $twoFactor = new TwoFactorSettings(
            secret: 'enc:SECRET',
            recoveryCodes: [],
            confirmedAt: new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );

        $result = ($this->handler($this->withUser(twoFactor: $twoFactor)))(new Login(
            email: 'ada@example.test',
            password: 'secret123',
        ));

        $this->assertTrue($result->requiresTwoFactor());
        $this->assertNull($result->token);
        $this->assertNotNull($result->challengeToken);
        $this->assertStringStartsWith('challenge:', $result->challengeToken);
    }

    #[Test]
    public function it_rejects_a_wrong_password(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        ($this->handler($this->withUser()))(new Login(email: 'ada@example.test', password: 'wrong'));
    }

    #[Test]
    public function it_rejects_an_unknown_email(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        ($this->handler(new InMemoryUserRepository))(new Login(email: 'nobody@example.test', password: 'secret123'));
    }

    #[Test]
    public function it_rejects_a_passwordless_user(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        ($this->handler($this->withUser(passwordHash: null)))(new Login(email: 'ada@example.test', password: 'secret123'));
    }
}
