<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\DataAccess\Mappers;

use NetCode\Identity\Domain\Invitation;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Infrastructure\DataAccess\Models\InvitationModel;

final class InvitationMapper
{
    public function toDomain(InvitationModel $model): Invitation
    {
        return Invitation::reconstitute(
            id: $model->id,
            realmId: $model->realm_id,
            email: new Email($model->email),
            tokenHash: $model->token_hash,
            metadata: $model->metadata,
            expiresAt: $model->expires_at,
            acceptedAt: $model->accepted_at,
            revokedAt: $model->revoked_at,
        );
    }

    public function hydrate(Invitation $invitation, InvitationModel $model): void
    {
        $model->id = $invitation->id();
        $model->realm_id = $invitation->realmId();
        $model->email = $invitation->email()->value();
        $model->token_hash = $invitation->tokenHash();
        $model->metadata = $invitation->metadata();
        $model->expires_at = $invitation->expiresAt();
        $model->accepted_at = $invitation->acceptedAt();
        $model->revoked_at = $invitation->revokedAt();
    }
}
