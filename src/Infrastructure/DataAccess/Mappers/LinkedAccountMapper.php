<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\DataAccess\Mappers;

use NetCode\Identity\Domain\LinkedAccount;
use NetCode\Identity\Infrastructure\DataAccess\Models\LinkedAccountModel;

final class LinkedAccountMapper
{
    public function toDomain(LinkedAccountModel $model): LinkedAccount
    {
        return LinkedAccount::reconstitute(
            id: $model->id,
            userId: $model->user_id,
            provider: $model->provider,
            providerId: $model->provider_id,
            createdAt: $model->created_at,
        );
    }

    public function hydrate(LinkedAccount $account, LinkedAccountModel $model): void
    {
        $model->id = $account->id();
        $model->user_id = $account->userId();
        $model->provider = $account->provider();
        $model->provider_id = $account->providerId();
        $model->created_at = $account->createdAt();
    }
}
