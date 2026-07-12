<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Events;

use DateTimeImmutable;
use NetCode\Domain\DomainEvent;
use NetCode\Identity\Domain\ValueObjects\InvitationId;

final readonly class InvitationAccepted implements DomainEvent
{
    public function __construct(
        public InvitationId $invitationId,
        public DateTimeImmutable $occurredOn,
    ) {}

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
