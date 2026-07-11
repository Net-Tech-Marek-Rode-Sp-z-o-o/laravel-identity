<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use DateTimeImmutable;
use NetCode\Kit\Clock;

final class FixedClock implements Clock
{
    public function __construct(
        private DateTimeImmutable $now = new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
    ) {}

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
