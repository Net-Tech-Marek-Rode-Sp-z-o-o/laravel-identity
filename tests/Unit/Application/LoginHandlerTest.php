<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application;

use NetCode\Identity\Application\Command\Login\Login;
use NetCode\Identity\Application\Command\Login\LoginHandler;
use NetCode\Identity\Domain\Exception\InvalidCredentialsException;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\UserId;
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
        );
    }

    private function withUser(string|null $passwordHash = 'hashed:secret123'): InMemoryUserRepository
    {
        $repo = new InMemoryUserRepository;
        $repo->save(User::reconstitute(
            id: UserId::random(),
            realmId: null,
            email: new Email('ada@example.test'),
            name: 'Ada',
            passwordHash: $passwordHash,
            deletedAt: null,
        ));

        return $repo;
    }

    #[Test]
    public function it_issues_a_token_for_valid_credentials(): void
    {
        $token = ($this->handler($this->withUser()))(new Login(
            email: 'ada@example.test',
            password: 'secret123',
        ));

        $this->assertStringStartsWith('token-', $token);
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
