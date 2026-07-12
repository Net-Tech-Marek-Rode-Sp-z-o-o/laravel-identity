<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Events;

use DateTimeImmutable;
use NetCode\Domain\DomainEvent;
use NetCode\Identity\Domain\ValueObjects\UserId;

final readonly class PasswordChanged implements DomainEvent
{
    public function __construct(
        public UserId $userId,
        private DateTimeImmutable $occurredOn,
    ) {}

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
