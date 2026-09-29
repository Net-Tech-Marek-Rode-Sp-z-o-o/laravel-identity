<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\DataAccess\Mappers;

use NetCode\Identity\Domain\EmailVerificationToken;
use NetCode\Identity\Infrastructure\DataAccess\Models\EmailVerificationTokenModel;

final class EmailVerificationTokenMapper
{
    public function toDomain(EmailVerificationTokenModel $model): EmailVerificationToken
    {
        return EmailVerificationToken::reconstitute(
            id: $model->id,
            userId: $model->user_id,
            tokenHash: $model->token_hash,
            expiresAt: $model->expires_at,
            usedAt: $model->used_at,
        );
    }

    public function hydrate(EmailVerificationToken $token, EmailVerificationTokenModel $model): void
    {
        $model->id = $token->id();
        $model->user_id = $token->userId();
        $model->token_hash = $token->tokenHash();
        $model->expires_at = $token->expiresAt();
        $model->used_at = $token->usedAt();
    }
}
