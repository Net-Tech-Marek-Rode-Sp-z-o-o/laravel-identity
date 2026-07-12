<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\DataAccess\Repositories;

use NetCode\Identity\Domain\Contracts\PasswordResetTokenRepository;
use NetCode\Identity\Domain\PasswordResetToken;
use NetCode\Identity\Domain\ValueObjects\PasswordResetTokenId;
use NetCode\Identity\Infrastructure\DataAccess\Mappers\PasswordResetTokenMapper;
use NetCode\Identity\Infrastructure\DataAccess\Models\PasswordResetTokenModel;

final readonly class EloquentPasswordResetTokenRepository implements PasswordResetTokenRepository
{
    public function __construct(
        private PasswordResetTokenMapper $mapper,
    ) {}

    public function nextId(): PasswordResetTokenId
    {
        return PasswordResetTokenId::random();
    }

    public function findByHash(string $tokenHash): PasswordResetToken|null
    {
        $model = PasswordResetTokenModel::query()->where('token_hash', $tokenHash)->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function save(PasswordResetToken $token): void
    {
        $model = PasswordResetTokenModel::query()->findOrNew($token->id()->value());
        $this->mapper->hydrate($token, $model);
        $model->save();
    }
}
