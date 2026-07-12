<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\DataAccess\Repositories;

use NetCode\Domain\DomainEventPublisher;
use NetCode\Identity\Domain\Contracts\InvitationRepository;
use NetCode\Identity\Domain\Invitation;
use NetCode\Identity\Domain\ValueObjects\InvitationId;
use NetCode\Identity\Infrastructure\DataAccess\Mappers\InvitationMapper;
use NetCode\Identity\Infrastructure\DataAccess\Models\InvitationModel;

final readonly class EloquentInvitationRepository implements InvitationRepository
{
    public function __construct(
        private InvitationMapper $mapper,
        private DomainEventPublisher $events,
    ) {}

    public function nextId(): InvitationId
    {
        return InvitationId::random();
    }

    public function findByHash(string $tokenHash): Invitation|null
    {
        $model = InvitationModel::query()->where('token_hash', $tokenHash)->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function findById(InvitationId $id): Invitation|null
    {
        $model = InvitationModel::query()->find($id->value());

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function save(Invitation $invitation): void
    {
        $model = InvitationModel::query()->findOrNew($invitation->id()->value());
        $this->mapper->hydrate($invitation, $model);
        $model->save();

        $this->events->publish(...$invitation->releaseEvents());
    }
}
