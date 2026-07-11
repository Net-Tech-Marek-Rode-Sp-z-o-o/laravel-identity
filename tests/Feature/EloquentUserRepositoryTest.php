<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Feature;

use DateTimeImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Identity\Domain\Contract\UserRepository;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\RealmId;
use NetCode\Identity\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class EloquentUserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function repository(): UserRepository
    {
        return $this->app->make(UserRepository::class);
    }

    private function user(RealmId|null $realm, string $email, string $name): User
    {
        return User::register(
            id: $this->repository()->nextId(),
            realmId: $realm,
            email: new Email($email),
            name: $name,
            passwordHash: 'hashed',
            now: new DateTimeImmutable,
        );
    }

    #[Test]
    public function it_persists_and_reconstitutes_a_user(): void
    {
        $repo = $this->repository();
        $user = $this->user(null, 'ada@example.test', 'Ada');
        $repo->save($user);

        $loaded = $repo->getById($user->id());

        $this->assertTrue($loaded->id()->equals($user->id()));
        $this->assertSame('ada@example.test', $loaded->email()->value());
        $this->assertSame('Ada', $loaded->name());
        $this->assertNull($loaded->realmId());
    }

    #[Test]
    public function it_scopes_email_lookup_by_realm(): void
    {
        $repo = $this->repository();
        $realm = RealmId::random();
        $repo->save($this->user(null, 'ada@example.test', 'Global'));
        $repo->save($this->user($realm, 'ada@example.test', 'Scoped'));

        $this->assertSame('Global', $repo->findByEmail(null, new Email('ada@example.test'))?->name());
        $this->assertSame('Scoped', $repo->findByEmail($realm, new Email('ada@example.test'))?->name());
    }

    #[Test]
    public function it_enforces_email_uniqueness_within_the_global_pool(): void
    {
        $repo = $this->repository();
        $repo->save($this->user(null, 'ada@example.test', 'First'));

        $this->expectException(QueryException::class);

        $repo->save($this->user(null, 'ada@example.test', 'Second'));
    }

    #[Test]
    public function a_soft_deleted_user_is_hidden_and_its_email_can_be_reused(): void
    {
        $repo = $this->repository();
        $user = $this->user(null, 'ada@example.test', 'First');
        $repo->save($user);
        $user->delete(new DateTimeImmutable);
        $repo->save($user);

        $this->assertNull($repo->findByEmail(null, new Email('ada@example.test')));

        $repo->save($this->user(null, 'ada@example.test', 'Second'));
        $this->assertSame('Second', $repo->findByEmail(null, new Email('ada@example.test'))?->name());
    }
}
