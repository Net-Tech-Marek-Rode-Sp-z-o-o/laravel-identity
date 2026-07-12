<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\DataAccess\Mappers;

use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\TwoFactorSettings;
use NetCode\Identity\Infrastructure\DataAccess\Models\TwoFactorModel;
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
            twoFactor: $this->toTwoFactor($model->twoFactor),
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

    public function hydrateTwoFactor(TwoFactorSettings $settings, TwoFactorModel $model): void
    {
        $model->secret = $settings->secret;
        $model->confirmed_at = $settings->confirmedAt;
        $model->recovery_codes = $settings->recoveryCodes;
    }

    private function toTwoFactor(TwoFactorModel|null $model): TwoFactorSettings|null
    {
        return $model === null ? null : new TwoFactorSettings(
            secret: $model->secret,
            recoveryCodes: $model->recovery_codes,
            confirmedAt: $model->confirmed_at,
        );
    }
}
