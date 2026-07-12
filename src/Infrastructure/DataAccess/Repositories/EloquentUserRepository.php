<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\DataAccess\Repositories;

use NetCode\Domain\DomainEventPublisher;
use NetCode\Identity\Domain\Contract\UserRepository;
use NetCode\Identity\Domain\Exception\UserNotFoundException;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\RealmId;
use NetCode\Identity\Domain\ValueObjects\TwoFactorSettings;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Infrastructure\DataAccess\Mappers\UserMapper;
use NetCode\Identity\Infrastructure\DataAccess\Models\TwoFactorModel;
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
        $query = UserModel::query()->with(UserModel::BASE_WITH)->where('email', $email->value());

        $query = $realm === null
            ? $query->whereNull('realm_id')
            : $query->where('realm_id', $realm->value());

        $model = $query->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function getById(UserId $id): User
    {
        $model = UserModel::query()->with(UserModel::BASE_WITH)->find($id->value());

        return $model === null
            ? throw UserNotFoundException::withId($id)
            : $this->mapper->toDomain($model);
    }

    public function save(User $user): void
    {
        $model = UserModel::query()->findOrNew($user->id()->value());
        $this->mapper->hydrate($user, $model);
        $model->save();

        $this->saveTwoFactor($user->id()->value(), $user->twoFactor());

        $this->events->publish(...$user->releaseEvents());
    }

    private function saveTwoFactor(string $userId, TwoFactorSettings|null $settings): void
    {
        if ($settings === null) {
            TwoFactorModel::query()->whereKey($userId)->delete();

            return;
        }

        $model = TwoFactorModel::query()->findOrNew($userId);
        $model->user_id = $userId;
        $this->mapper->hydrateTwoFactor($settings, $model);
        $model->save();
    }
}
