<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\DataAccess\Repositories;

use NetCode\Identity\Domain\Contracts\LinkedAccountRepository;
use NetCode\Identity\Domain\LinkedAccount;
use NetCode\Identity\Domain\SocialProvider;
use NetCode\Identity\Domain\ValueObjects\LinkedAccountId;
use NetCode\Identity\Infrastructure\DataAccess\Mappers\LinkedAccountMapper;
use NetCode\Identity\Infrastructure\DataAccess\Models\LinkedAccountModel;

final readonly class EloquentLinkedAccountRepository implements LinkedAccountRepository
{
    public function __construct(
        private LinkedAccountMapper $mapper,
    ) {}

    public function nextId(): LinkedAccountId
    {
        return LinkedAccountId::random();
    }

    public function findByProvider(SocialProvider $provider, string $providerId): LinkedAccount|null
    {
        $model = LinkedAccountModel::query()
            ->where('provider', $provider->value)
            ->where('provider_id', $providerId)
            ->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function save(LinkedAccount $account): void
    {
        $model = LinkedAccountModel::query()->findOrNew($account->id()->value());
        $this->mapper->hydrate($account, $model);
        $model->save();
    }
}
