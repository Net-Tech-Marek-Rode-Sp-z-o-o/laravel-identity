<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Domain\DomainEvent;
use NetCode\Identity\Domain\Contracts\UserRepository;
use NetCode\Identity\Domain\Exceptions\UserNotFoundException;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\RealmId;
use NetCode\Identity\Domain\ValueObjects\UserId;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $users = [];

    /** @var list<DomainEvent> */
    public array $published = [];

    public function nextId(): UserId
    {
        return UserId::random();
    }

    public function findByEmail(RealmId|null $realm, Email $email): User|null
    {
        foreach ($this->users as $user) {
            if (! $user->isDeleted()
                && $user->email()->equals($email)
                && $this->sameRealm($user->realmId(), $realm)) {
                return $user;
            }
        }

        return null;
    }

    public function getById(UserId $id): User
    {
        return $this->users[$id->value()] ?? throw UserNotFoundException::withId($id);
    }

    public function save(User $user): void
    {
        $this->users[$user->id()->value()] = $user;

        foreach ($user->releaseEvents() as $event) {
            $this->published[] = $event;
        }
    }

    private function sameRealm(RealmId|null $a, RealmId|null $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }

        return $a->equals($b);
    }
}
