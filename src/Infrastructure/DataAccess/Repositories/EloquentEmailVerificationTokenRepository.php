<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\DataAccess\Repositories;

use NetCode\Identity\Domain\Contracts\EmailVerificationTokenRepository;
use NetCode\Identity\Domain\EmailVerificationToken;
use NetCode\Identity\Domain\ValueObjects\EmailVerificationTokenId;
use NetCode\Identity\Infrastructure\DataAccess\Mappers\EmailVerificationTokenMapper;
use NetCode\Identity\Infrastructure\DataAccess\Models\EmailVerificationTokenModel;

final readonly class EloquentEmailVerificationTokenRepository implements EmailVerificationTokenRepository
{
    public function __construct(
        private EmailVerificationTokenMapper $mapper,
    ) {}

    public function nextId(): EmailVerificationTokenId
    {
        return EmailVerificationTokenId::random();
    }

    public function findByHash(string $tokenHash): EmailVerificationToken|null
    {
        $model = EmailVerificationTokenModel::query()->where('token_hash', $tokenHash)->first();

        return $model === null ? null : $this->mapper->toDomain(model: $model);
    }

    public function save(EmailVerificationToken $token): void
    {
        $model = EmailVerificationTokenModel::query()->findOrNew($token->id()->value());
        $this->mapper->hydrate(token: $token, model: $model);
        $model->save();
    }
}
