<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\DataAccess\Mappers;

use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Infrastructure\DataAccess\Models\UserModel;

final class UserMapper
{
    public function toDomain(UserModel $model): User
    {
        return User::reconstitute(
            id: $model->id,
            realmId: $model->realm_id,
            email: new Email($model->email),
            name: $model->name,
            passwordHash: $model->password_hash,
            deletedAt: $model->deleted_at,
        );
    }

    public function hydrate(User $user, UserModel $model): void
    {
        $model->id = $user->id();
        $model->realm_id = $user->realmId();
        $model->email = $user->email()->value();
        $model->name = $user->name();
        $model->password_hash = $user->passwordHash();
        $model->deleted_at = $user->deletedAt();
    }
}
