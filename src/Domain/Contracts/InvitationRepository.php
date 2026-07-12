<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Contracts;

use NetCode\Identity\Domain\Invitation;
use NetCode\Identity\Domain\ValueObjects\InvitationId;

interface InvitationRepository
{
    public function nextId(): InvitationId;

    public function findByHash(string $tokenHash): Invitation|null;

    public function findById(InvitationId $id): Invitation|null;

    public function save(Invitation $invitation): void;
}
