<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use NetCode\Identity\Application\Commands\DeleteAccount\DeleteAccount;
use NetCode\Identity\Application\Commands\DeleteAccount\DeleteAccountHandler;
use NetCode\Identity\Domain\Events\UserDeleted;
use NetCode\Identity\Domain\Exceptions\PasswordConfirmationFailedException;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\RealmId;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Tests\Support\FakePasswordHasher;
use NetCode\Identity\Tests\Support\FixedClock;
use NetCode\Identity\Tests\Support\InMemoryUserRepository;
use NetCode\Identity\Tests\Support\RecordingAccountDeletionHook;
use NetCode\Identity\Tests\Support\RecordingTokenRevoker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DeleteAccountHandlerTest extends TestCase
{
    private InMemoryUserRepository $users;

    private RecordingTokenRevoker $tokens;

    private RecordingAccountDeletionHook $hook;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository;
        $this->tokens = new RecordingTokenRevoker;
        $this->hook = new RecordingAccountDeletionHook;
    }

    private function handler(): DeleteAccountHandler
    {
        return new DeleteAccountHandler(
            clock: new FixedClock,
            users: $this->users,
            hasher: new FakePasswordHasher,
            tokens: $this->tokens,
            hook: $this->hook,
        );
    }

    private function seed(string|null $passwordHash = 'hashed:secret123', RealmId|null $realmId = null): UserId
    {
        $id = UserId::random();

        $this->users->save(user: User::reconstitute(
            id: $id,
            realmId: $realmId,
            email: new Email('ada@example.test'),
            name: 'Ada',
            passwordHash: $passwordHash,
            twoFactor: null,
            deletedAt: null,
        ));

        return $id;
    }

    #[Test]
    public function it_deletes_the_user_and_records_the_event(): void
    {
        $id = $this->seed();

        ($this->handler())(new DeleteAccount(userId: $id, password: 'secret123'));

        $user = $this->users->getById(id: $id);
        $this->assertTrue($user->isDeleted());
        $this->assertEquals((new FixedClock)->now(), $user->deletedAt());
        $this->assertCount(1, $this->users->published);
        $this->assertContainsOnlyInstancesOf(UserDeleted::class, $this->users->published);
    }

    #[Test]
    public function it_runs_the_deletion_hook_with_the_deleted_user(): void
    {
        $realm = RealmId::random();
        $id = $this->seed(realmId: $realm);

        ($this->handler())(new DeleteAccount(userId: $id, password: 'secret123'));

        $this->assertSame($id->value(), $this->hook->user?->id);
        $this->assertSame('Ada', $this->hook->user?->name);
        $this->assertSame('ada@example.test', $this->hook->user?->email);
        $this->assertSame($realm->value(), $this->hook->user?->realmId);
    }

    #[Test]
    public function it_revokes_every_token(): void
    {
        $id = $this->seed();

        ($this->handler())(new DeleteAccount(userId: $id, password: 'secret123'));

        $this->assertTrue($this->tokens->allRevoked);
    }

    #[Test]
    public function it_rejects_a_wrong_password(): void
    {
        $id = $this->seed();

        try {
            ($this->handler())(new DeleteAccount(userId: $id, password: 'wrong'));
            $this->fail('Expected PasswordConfirmationFailedException.');
        } catch (PasswordConfirmationFailedException) {
            $this->assertFalse($this->users->getById(id: $id)->isDeleted());
            $this->assertFalse($this->tokens->allRevoked);
            $this->assertNull($this->hook->user);
        }
    }

    #[Test]
    public function it_rejects_a_missing_password_for_a_user_with_a_password(): void
    {
        $id = $this->seed();

        $this->expectException(PasswordConfirmationFailedException::class);

        ($this->handler())(new DeleteAccount(userId: $id, password: null));
    }

    #[Test]
    public function it_deletes_a_passwordless_user_without_a_password(): void
    {
        $id = $this->seed(passwordHash: null);

        ($this->handler())(new DeleteAccount(userId: $id, password: null));

        $this->assertTrue($this->users->getById(id: $id)->isDeleted());
        $this->assertSame($id->value(), $this->hook->user?->id);
    }
}
