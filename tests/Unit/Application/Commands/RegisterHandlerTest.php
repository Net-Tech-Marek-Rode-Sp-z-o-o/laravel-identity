<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use NetCode\Identity\Application\Commands\Register\Register;
use NetCode\Identity\Application\Commands\Register\RegisterHandler;
use NetCode\Identity\Domain\Events\UserRegistered;
use NetCode\Identity\Domain\Exceptions\EmailAlreadyTakenException;
use NetCode\Identity\Domain\ValueObjects\RealmId;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Infrastructure\Registration\NoRegistrationPayload;
use NetCode\Identity\Tests\Support\EmailVerificationIssuerFactory;
use NetCode\Identity\Tests\Support\FakePasswordHasher;
use NetCode\Identity\Tests\Support\FixedClock;
use NetCode\Identity\Tests\Support\FixedRealmContext;
use NetCode\Identity\Tests\Support\InMemoryUserRepository;
use NetCode\Identity\Tests\Support\RecordingPostRegistrationHook;
use NetCode\Identity\Tests\Support\SpyEmailVerificationNotifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RegisterHandlerTest extends TestCase
{
    private RecordingPostRegistrationHook $hook;

    private SpyEmailVerificationNotifier $notifier;

    protected function setUp(): void
    {
        $this->hook = new RecordingPostRegistrationHook;
        $this->notifier = new SpyEmailVerificationNotifier;
    }

    private function handler(InMemoryUserRepository $repo, FixedRealmContext $realm = new FixedRealmContext): RegisterHandler
    {
        return new RegisterHandler(
            clock: new FixedClock,
            realm: $realm,
            users: $repo,
            hasher: new FakePasswordHasher,
            hook: $this->hook,
            verification: EmailVerificationIssuerFactory::make(notifier: $this->notifier),
        );
    }

    private function command(string $email = 'ada@example.test', string $name = 'Ada'): Register
    {
        return new Register(name: $name, email: $email, password: 'secret123', payload: new NoRegistrationPayload);
    }

    #[Test]
    public function it_registers_a_user_hashes_the_password_and_records_the_event(): void
    {
        $repo = new InMemoryUserRepository;

        $id = ($this->handler($repo))($this->command(email: 'Ada@Example.test'));

        $user = $repo->getById(UserId::fromString($id));
        $this->assertSame('Ada', $user->name());
        $this->assertSame('ada@example.test', $user->email()->value());
        $this->assertSame('hashed:secret123', $user->passwordHash());
        $this->assertContainsOnlyInstancesOf(UserRegistered::class, $repo->published);
    }

    #[Test]
    public function it_runs_the_post_registration_hook_with_the_new_user_and_payload(): void
    {
        $repo = new InMemoryUserRepository;

        $id = ($this->handler($repo))($this->command());

        $this->assertSame($id, $this->hook->user?->id);
        $this->assertSame('ada@example.test', $this->hook->user?->email);
        $this->assertInstanceOf(NoRegistrationPayload::class, $this->hook->payload);
    }

    #[Test]
    public function it_registers_into_the_current_realm(): void
    {
        $realm = RealmId::random();
        $repo = new InMemoryUserRepository;

        $id = ($this->handler($repo, new FixedRealmContext($realm)))($this->command());

        $this->assertTrue($repo->getById(UserId::fromString($id))->realmId()?->equals($realm));
    }

    #[Test]
    public function it_rejects_a_duplicate_email_in_the_same_realm(): void
    {
        $repo = new InMemoryUserRepository;
        $handler = $this->handler($repo);
        $handler($this->command());

        $this->expectException(EmailAlreadyTakenException::class);

        $handler($this->command(name: 'Ada II'));
    }

    #[Test]
    public function it_sends_an_email_verification_token(): void
    {
        $repo = new InMemoryUserRepository;

        $id = ($this->handler($repo))($this->command());

        $this->assertSame('ada@example.test', $this->notifier->email?->value());
        $this->assertSame('the-token', $this->notifier->token);
        $this->assertFalse($repo->getById(UserId::fromString($id))->isEmailVerified());
    }
}
