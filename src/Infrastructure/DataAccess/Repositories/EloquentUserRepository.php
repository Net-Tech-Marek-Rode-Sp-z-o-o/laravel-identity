<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\DataAccess\Repositories;

use NetCode\Domain\DomainEventPublisher;
use NetCode\Identity\Domain\Contract\UserRepository;
use NetCode\Identity\Domain\Exception\UserNotFoundException;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\RealmId;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Infrastructure\DataAccess\Mappers\UserMapper;
use NetCode\Identity\Infrastructure\DataAccess\Models\UserModel;

final readonly class EloquentUserRepository implements UserRepository
{
    public function __construct(
        private UserMapper $mapper,
        private DomainEventPublisher $events,
    ) {}

    public function nextId(): UserId
    {
        return UserId::random();
    }

    public function findByEmail(RealmId|null $realm, Email $email): User|null
    {
        $query = UserModel::query()->where('email', $email->value());

        $query = $realm === null
            ? $query->whereNull('realm_id')
            : $query->where('realm_id', $realm->value());

        $model = $query->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function getById(UserId $id): User
    {
        $model = UserModel::query()->find($id->value());

        return $model === null
            ? throw UserNotFoundException::withId($id)
            : $this->mapper->toDomain($model);
    }

    public function save(User $user): void
    {
        $model = UserModel::query()->findOrNew($user->id()->value());
        $this->mapper->hydrate($user, $model);
        $model->save();

        $this->events->publish(...$user->releaseEvents());
    }
}
