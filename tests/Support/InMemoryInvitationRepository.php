<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Domain\DomainEvent;
use NetCode\Identity\Domain\Contracts\InvitationRepository;
use NetCode\Identity\Domain\Invitation;
use NetCode\Identity\Domain\ValueObjects\InvitationId;

final class InMemoryInvitationRepository implements InvitationRepository
{
    /** @var array<string, Invitation> */
    private array $invitations = [];

    /** @var list<DomainEvent> */
    public array $published = [];

    public function nextId(): InvitationId
    {
        return InvitationId::random();
    }

    public function findByHash(string $tokenHash): Invitation|null
    {
        foreach ($this->invitations as $invitation) {
            if ($invitation->tokenHash() === $tokenHash) {
                return $invitation;
            }
        }

        return null;
    }

    public function findById(InvitationId $id): Invitation|null
    {
        return $this->invitations[$id->value()] ?? null;
    }

    public function save(Invitation $invitation): void
    {
        $this->invitations[$invitation->id()->value()] = $invitation;

        foreach ($invitation->releaseEvents() as $event) {
            $this->published[] = $event;
        }
    }
}
