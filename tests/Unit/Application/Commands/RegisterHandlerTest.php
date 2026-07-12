<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use NetCode\Identity\Application\Commands\Register\Register;
use NetCode\Identity\Application\Commands\Register\RegisterHandler;
use NetCode\Identity\Domain\Events\UserRegistered;
use NetCode\Identity\Domain\Exceptions\EmailAlreadyTakenException;
use NetCode\Identity\Domain\ValueObjects\RealmId;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Tests\Support\FakePasswordHasher;
use NetCode\Identity\Tests\Support\FixedClock;
use NetCode\Identity\Tests\Support\FixedRealmContext;
use NetCode\Identity\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RegisterHandlerTest extends TestCase
{
    private function handler(InMemoryUserRepository $repo, FixedRealmContext $realm = new FixedRealmContext): RegisterHandler
    {
        return new RegisterHandler(
            clock: new FixedClock,
            realm: $realm,
            users: $repo,
            hasher: new FakePasswordHasher,
        );
    }

    #[Test]
    public function it_registers_a_user_hashes_the_password_and_records_the_event(): void
    {
        $repo = new InMemoryUserRepository;

        $id = ($this->handler($repo))(new Register(
            name: 'Ada',
            email: 'Ada@Example.test',
            password: 'secret123',
        ));

        $user = $repo->getById(UserId::fromString($id));
        $this->assertSame('Ada', $user->name());
        $this->assertSame('ada@example.test', $user->email()->value());
        $this->assertSame('hashed:secret123', $user->passwordHash());
        $this->assertContainsOnlyInstancesOf(UserRegistered::class, $repo->published);
    }

    #[Test]
    public function it_registers_into_the_current_realm(): void
    {
        $realm = RealmId::random();
        $repo = new InMemoryUserRepository;

        $id = ($this->handler($repo, new FixedRealmContext($realm)))(new Register(
            name: 'Ada',
            email: 'ada@example.test',
            password: 'secret123',
        ));

        $this->assertTrue($repo->getById(UserId::fromString($id))->realmId()?->equals($realm));
    }

    #[Test]
    public function it_rejects_a_duplicate_email_in_the_same_realm(): void
    {
        $repo = new InMemoryUserRepository;
        $handler = $this->handler($repo);
        $handler(new Register(name: 'Ada', email: 'ada@example.test', password: 'secret123'));

        $this->expectException(EmailAlreadyTakenException::class);

        $handler(new Register(name: 'Ada II', email: 'ada@example.test', password: 'secret123'));
    }
}
